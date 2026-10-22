<script setup lang="ts">
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { getUser, clearAuth } from '@/lib/auth'
import { api } from '@/lib/api'

const router = useRouter()
const user = getUser()

async function logout() {
  try {
    await api.post('/logout', {})
  } finally {
    clearAuth()
    router.push('/login')
  }
}
</script>

<template>
  <div class="min-h-screen bg-background">
    <header class="border-b">
      <div class="max-w-6xl mx-auto px-4 h-14 flex items-center justify-between">
        <nav class="flex items-center gap-6">
          <span class="font-bold text-primary text-lg">Toko Yafi</span>
          <RouterLink
            to="/products"
            class="text-sm font-medium text-muted-foreground transition-colors hover:text-primary"
            active-class="text-primary border-b-2 border-primary pb-0.5"
          >
            Produk
          </RouterLink>
          <RouterLink
            to="/categories"
            class="text-sm font-medium text-muted-foreground transition-colors hover:text-primary"
            active-class="text-primary border-b-2 border-primary pb-0.5"
          >
            Kategori
          </RouterLink>
        </nav>
        <div class="flex items-center gap-3">
          <span class="text-sm text-muted-foreground">{{ user?.name }}</span>
          <button
            class="text-sm font-medium hover:text-primary transition-colors"
            @click="logout"
          >
            Keluar
          </button>
        </div>
      </div>
    </header>
    <main class="max-w-6xl mx-auto px-4 py-6">
      <RouterView />
    </main>
  </div>
</template>
