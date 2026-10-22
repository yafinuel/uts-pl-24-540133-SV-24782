<script setup lang="ts">
import { ref, watch } from 'vue'
import { api } from '@/lib/api'
import type { Category, Product } from '@/types'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

const props = defineProps<{
  open: boolean
  product?: Product | null
  categories: Category[]
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  saved: []
}>()

const categoryId = ref('')
const sku = ref('')
const name = ref('')
const price = ref(0)
const stock = ref(0)
const loading = ref(false)
const fieldErrors = ref<Record<string, string>>({})

watch(
  () => props.open,
  (val) => {
    if (val) {
      if (props.product) {
        categoryId.value = String(props.product.category.id)
        sku.value = props.product.sku
        name.value = props.product.name
        price.value = props.product.price
        stock.value = props.product.stock
      } else {
        categoryId.value = ''
        sku.value = ''
        name.value = ''
        price.value = 0
        stock.value = 0
      }
      fieldErrors.value = {}
    }
  },
)

async function save() {
  loading.value = true
  fieldErrors.value = {}

  const payload = {
    category_id: parseInt(categoryId.value),
    sku: sku.value,
    name: name.value,
    price: price.value,
    stock: stock.value,
  }

  try {
    if (props.product) {
      await api.put(`/products/${props.product.id}`, payload)
    } else {
      await api.post('/products', payload)
    }
    emit('update:open', false)
    emit('saved')
  } catch (err: unknown) {
    const e = err as { errors?: Record<string, string[]> }
    if (e.errors) {
      for (const [field, messages] of Object.entries(e.errors)) {
        fieldErrors.value[field] = messages[0] ?? ''
      }
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="max-w-md">
      <DialogHeader>
        <DialogTitle>{{ product ? 'Ubah Produk' : 'Tambah Produk' }}</DialogTitle>
      </DialogHeader>
      <form class="space-y-4" @submit.prevent="save">
        <div class="space-y-1">
          <Label>Kategori</Label>
          <Select v-model="categoryId">
            <SelectTrigger>
              <SelectValue placeholder="Pilih kategori" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="cat in categories" :key="cat.id" :value="String(cat.id)">
                {{ cat.name }}
              </SelectItem>
            </SelectContent>
          </Select>
          <p v-if="fieldErrors.category_id" class="text-xs text-destructive">
            {{ fieldErrors.category_id }}
          </p>
        </div>

        <div class="space-y-1">
          <Label for="sku">SKU</Label>
          <Input id="sku" v-model="sku" placeholder="MNM-001" />
          <p v-if="fieldErrors.sku" class="text-xs text-destructive">{{ fieldErrors.sku }}</p>
        </div>

        <div class="space-y-1">
          <Label for="prod-name">Nama</Label>
          <Input id="prod-name" v-model="name" placeholder="Nama produk" />
          <p v-if="fieldErrors.name" class="text-xs text-destructive">{{ fieldErrors.name }}</p>
        </div>

        <div class="space-y-1">
          <Label for="price">Harga (Rp)</Label>
          <Input id="price" v-model.number="price" type="number" min="0" />
          <p v-if="fieldErrors.price" class="text-xs text-destructive">{{ fieldErrors.price }}</p>
        </div>

        <div class="space-y-1">
          <Label for="stock">Stok</Label>
          <Input id="stock" v-model.number="stock" type="number" min="0" />
          <p v-if="fieldErrors.stock" class="text-xs text-destructive">{{ fieldErrors.stock }}</p>
        </div>

        <DialogFooter>
          <Button type="submit" :disabled="loading">
            {{ loading ? 'Menyimpan...' : 'Simpan' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
