<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { api } from '@/lib/api'
import type { Category, Product, Paginated } from '@/types'
import { toast } from 'vue-sonner'
import { formatRupiah } from '@/lib/format'
import ProductFormDialog from '@/components/ProductFormDialog.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Pencil, Trash2, Plus } from '@lucide/vue'

const products = ref<Product[]>([])
const categories = ref<Category[]>([])
const loading = ref(false)
const loadError = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)
const searchInput = ref('')
const search = ref('')
const categoryFilter = ref('all')

const formOpen = ref(false)
const editProduct = ref<Product | null>(null)
const deleteOpen = ref(false)
const deleteTarget = ref<Product | null>(null)
const deleteLoading = ref(false)

let debounceTimer: ReturnType<typeof setTimeout>

watch(searchInput, (val) => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    search.value = val
    currentPage.value = 1
  }, 300)
})

watch(categoryFilter, () => {
  currentPage.value = 1
})

watch([search, categoryFilter, currentPage], loadProducts)

async function loadProducts() {
  loading.value = true
  loadError.value = false
  try {
    const params = new URLSearchParams()
    params.set('page', String(currentPage.value))
    if (search.value) params.set('search', search.value)
    if (categoryFilter.value !== 'all') params.set('category_id', categoryFilter.value)

    const data = await api.get<Paginated<Product>>(`/products?${params}`)
    products.value = data.data
    lastPage.value = data.meta.last_page
  } catch {
    loadError.value = true
  } finally {
    loading.value = false
  }
}

async function loadCategories() {
  try {
    const data = await api.get<{ data: Category[] }>('/categories')
    categories.value = data.data
  } catch {
    // kategori gagal dimuat tidak menghentikan halaman
  }
}

function openAdd() {
  editProduct.value = null
  formOpen.value = true
}

function openEdit(product: Product) {
  editProduct.value = product
  formOpen.value = true
}

function openDelete(product: Product) {
  deleteTarget.value = product
  deleteOpen.value = true
}

async function confirmDelete() {
  if (!deleteTarget.value) return
  deleteLoading.value = true
  try {
    await api.delete(`/products/${deleteTarget.value.id}`)
    toast('Produk berhasil dihapus')
    deleteOpen.value = false
    if (products.value.length === 1 && currentPage.value > 1) {
      currentPage.value -= 1
    } else {
      await loadProducts()
    }
  } catch (err: unknown) {
    const e = err as { message: string }
    toast.error(e.message ?? 'Terjadi kesalahan. Coba lagi.')
    deleteOpen.value = false
  } finally {
    deleteLoading.value = false
  }
}

function onSaved() {
  const isEdit = !!editProduct.value
  toast(isEdit ? 'Produk berhasil diperbarui' : 'Produk berhasil ditambahkan')
  loadProducts()
}

const hasFilter = () => search.value !== '' || categoryFilter.value !== 'all'

onMounted(() => {
  loadCategories()
  loadProducts()
})
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">Produk</h1>
      <Button @click="openAdd">
        <Plus class="w-4 h-4 mr-1" />
        Tambah Produk
      </Button>
    </div>

    <div class="flex gap-3">
      <Input
        v-model="searchInput"
        placeholder="Cari nama atau SKU..."
        class="max-w-xs"
      />
      <Select v-model="categoryFilter">
        <SelectTrigger class="w-48">
          <SelectValue placeholder="Semua kategori" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">Semua kategori</SelectItem>
          <SelectItem v-for="cat in categories" :key="cat.id" :value="String(cat.id)">
            {{ cat.name }}
          </SelectItem>
        </SelectContent>
      </Select>
    </div>

    <div v-if="loading" class="space-y-2">
      <Skeleton v-for="i in 5" :key="i" class="h-12 w-full" />
    </div>

    <div v-else-if="loadError" class="text-center py-8 text-muted-foreground">
      <p>Gagal memuat data.</p>
      <Button variant="outline" class="mt-2" @click="loadProducts">Coba lagi</Button>
    </div>

    <div
      v-else-if="products.length === 0 && !hasFilter()"
      class="text-center py-8 text-muted-foreground"
    >
      <p>Belum ada produk.</p>
      <Button class="mt-2" @click="openAdd">Tambah Produk</Button>
    </div>

    <div
      v-else-if="products.length === 0 && hasFilter()"
      class="text-center py-8 text-muted-foreground"
    >
      <p>Tidak ada produk yang cocok.</p>
    </div>

    <template v-else>
      <div class="overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>SKU</TableHead>
              <TableHead>Nama</TableHead>
              <TableHead>Kategori</TableHead>
              <TableHead>Harga</TableHead>
              <TableHead>Stok</TableHead>
              <TableHead class="w-24">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="product in products" :key="product.id" class="hover:bg-accent">
              <TableCell class="font-mono text-sm">{{ product.sku }}</TableCell>
              <TableCell>{{ product.name }}</TableCell>
              <TableCell>{{ product.category.name }}</TableCell>
              <TableCell>{{ formatRupiah(product.price) }}</TableCell>
              <TableCell>
                <Badge v-if="product.stock === 0" variant="destructive">Habis</Badge>
                <span v-else>{{ product.stock }}</span>
              </TableCell>
              <TableCell>
                <div class="flex gap-2">
                  <button class="hover:text-primary transition-colors" @click="openEdit(product)">
                    <Pencil class="w-4 h-4" />
                  </button>
                  <button
                    class="hover:text-destructive transition-colors"
                    @click="openDelete(product)"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <div class="flex items-center justify-between text-sm text-muted-foreground">
        <span>Halaman {{ currentPage }} dari {{ lastPage }}</span>
        <div class="flex gap-2">
          <Button
            variant="outline"
            size="sm"
            :disabled="currentPage <= 1"
            @click="currentPage--"
          >
            Sebelumnya
          </Button>
          <Button
            variant="outline"
            size="sm"
            :disabled="currentPage >= lastPage"
            @click="currentPage++"
          >
            Berikutnya
          </Button>
        </div>
      </div>
    </template>
  </div>

  <ProductFormDialog
    v-model:open="formOpen"
    :product="editProduct"
    :categories="categories"
    @saved="onSaved"
  />

  <ConfirmDialog
    v-model:open="deleteOpen"
    title="Hapus Produk"
    :description="`Hapus produk ${deleteTarget?.name}?`"
    :loading="deleteLoading"
    @confirm="confirmDelete"
  />
</template>
