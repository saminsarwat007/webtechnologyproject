<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from './stores/auth.js'
import NavBar from './components/NavBar.vue'
import ToastNotification from './components/ToastNotification.vue'

const auth = useAuthStore()
const route = useRoute()
const showNav = computed(() => auth.isAuthenticated && !route.meta.public)
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <NavBar v-if="showNav" />

    <main class="flex-1">
      <RouterView v-slot="{ Component }">
        <Transition name="fade" mode="out-in">
          <component :is="Component" />
        </Transition>
      </RouterView>
    </main>

    <ToastNotification />
  </div>
</template>
