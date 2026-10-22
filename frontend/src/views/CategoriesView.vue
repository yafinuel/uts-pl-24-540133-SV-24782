<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { api } from '@/lib/api'
import type { Category } from '@/types'
import { toast } from 'vue-sonner'
import CategoryFormDialog from '@/components/CategoryFormDialog.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Pencil, Trash2, Plus } from '@lucide/vue'

const categories = ref<Category[]>([])
const loading = ref(false)
const loadError = ref(false)
const formOpen = ref(false)
const editCategory = ref<Category | null>(null)
const deleteOpen = ref(false)
const deleteTarget = ref<Category | null>(null)
const deleteLoading = ref(false)

async function loadCategories() {
  loading.value = true
  loadError.value = false
  try {
    const data = await api.get<{ data: Category[] }>('/categories')
    categories.value = data.data
  } catch {
    loadError.value = true
  } finally {
    loading.value = false
  }
}

function openAdd() {
  editCategory.value = null
  formOpen.value = true
}

function openEdit(category: Category) {
  editCategory.value = category
  formOpen.value = true
}

function openDelete(category: Category) {
  deleteTarget.value = category
  deleteOpen.value = true
}

async function confirmDelete() {
  if (!deleteTarget.value) return
  deleteLoading.value = true
  try {
    await api.delete(`/categories/${deleteTarget.value.id}`)
    toast('Kategori berhasil dihapus')
    deleteOpen.value = false
    await loadCategories()
  } catch (err: unknown) {
    const e = err as { message: string }
    toast.error(e.message ?? 'Terjadi kesalahan. Coba lagi.')
    deleteOpen.value = false
  } finally {
    deleteLoading.value = false
  }
}

function onSaved() {
  const isEdit = !!editCategory.value
  toast(isEdit ? 'Kategori berhasil diperbarui' : 'Kategori berhasil ditambahkan')
  loadCategories()
}

onMounted(loadCategories)
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">Kategori</h1>
      <Button @click="openAdd">
        <Plus class="w-4 h-4 mr-1" />
        Tambah Kategori
      </Button>
    </div>

    <div v-if="loading" class="space-y-2">
      <Skeleton v-for="i in 3" :key="i" class="h-10 w-full" />
    </div>

    <div v-else-if="loadError" class="text-center py-8 text-muted-foreground">
      <p>Gagal memuat data.</p>
      <Button variant="outline" class="mt-2" @click="loadCategories">Coba lagi</Button>
    </div>

    <div v-else-if="categories.length === 0" class="text-center py-8 text-muted-foreground">
      <p>Belum ada kategori.</p>
      <Button class="mt-2" @click="openAdd">Tambah Kategori</Button>
    </div>

    <Table v-else>
      <TableHeader>
        <TableRow>
          <TableHead>Nama</TableHead>
          <TableHead class="w-24">Aksi</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        <TableRow v-for="cat in categories" :key="cat.id" class="hover:bg-accent">
          <TableCell>{{ cat.name }}</TableCell>
          <TableCell>
            <div class="flex gap-2">
              <button class="hover:text-primary transition-colors" @click="openEdit(cat)">
                <Pencil class="w-4 h-4" />
              </button>
              <button class="hover:text-destructive transition-colors" @click="openDelete(cat)">
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>
  </div>

  <CategoryFormDialog
    v-model:open="formOpen"
    :category="editCategory"
    @saved="onSaved"
  />

  <ConfirmDialog
    v-model:open="deleteOpen"
    title="Hapus Kategori"
    :description="`Hapus kategori ${deleteTarget?.name}?`"
    :loading="deleteLoading"
    @confirm="confirmDelete"
  />
</template>
