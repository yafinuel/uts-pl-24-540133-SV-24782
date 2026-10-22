<script setup lang="ts">
import { ref, watch } from 'vue'
import { api } from '@/lib/api'
import type { Category } from '@/types'
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

const props = defineProps<{
  open: boolean
  category?: Category | null
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  saved: []
}>()

const name = ref('')
const loading = ref(false)
const fieldErrors = ref<Record<string, string>>({})

watch(
  () => props.open,
  (val) => {
    if (val) {
      name.value = props.category?.name ?? ''
      fieldErrors.value = {}
    }
  },
)

async function save() {
  loading.value = true
  fieldErrors.value = {}

  try {
    if (props.category) {
      await api.put(`/categories/${props.category.id}`, { name: name.value })
    } else {
      await api.post('/categories', { name: name.value })
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
    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{ category ? 'Ubah Kategori' : 'Tambah Kategori' }}</DialogTitle>
      </DialogHeader>
      <form class="space-y-4" @submit.prevent="save">
        <div class="space-y-1">
          <Label for="cat-name">Nama</Label>
          <Input id="cat-name" v-model="name" placeholder="Nama kategori" />
          <p v-if="fieldErrors.name" class="text-xs text-destructive">{{ fieldErrors.name }}</p>
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
