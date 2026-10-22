<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/lib/api'
import { setAuth } from '@/lib/auth'
import type { ApiError } from '@/lib/api'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'

const router = useRouter()

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')
const fieldErrors = ref<Record<string, string>>({})

async function login() {
  loading.value = true
  error.value = ''
  fieldErrors.value = {}

  try {
    const data = await api.post<{
      data: { token: string; user: { id: number; name: string; email: string } }
    }>('/login', { email: email.value, password: password.value })
    setAuth(data.data.token, data.data.user)
    router.push('/products')
  } catch (err: unknown) {
    const e = err as ApiError
    if (e.status === 422 && e.errors) {
      for (const [field, messages] of Object.entries(e.errors)) {
        fieldErrors.value[field] = messages[0] ?? ''
      }
    } else {
      error.value = e.message ?? 'Terjadi kesalahan. Coba lagi.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-background p-4">
    <Card class="w-full max-w-sm">
      <CardHeader>
        <CardTitle>Masuk ke Toko Yafi</CardTitle>
      </CardHeader>
      <CardContent>
        <form class="space-y-4" @submit.prevent="login">
          <div v-if="error" class="text-sm text-destructive">{{ error }}</div>

          <div class="space-y-1">
            <Label for="email">Email</Label>
            <Input id="email" v-model="email" type="email" placeholder="admin@tokoyafi.test" />
            <p v-if="fieldErrors.email" class="text-xs text-destructive">{{ fieldErrors.email }}</p>
          </div>

          <div class="space-y-1">
            <Label for="password">Password</Label>
            <Input id="password" v-model="password" type="password" />
            <p v-if="fieldErrors.password" class="text-xs text-destructive">
              {{ fieldErrors.password }}
            </p>
          </div>

          <Button type="submit" class="w-full" :disabled="loading">
            {{ loading ? 'Memproses...' : 'Masuk' }}
          </Button>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
