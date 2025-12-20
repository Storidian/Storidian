<script setup>
  import CommanderSidebarSection from './commander-sidebar-section.vue'
  import CommanderSidebarFooter from './commander-sidebar-footer.vue'
  import { Home, Folder, Camera } from 'lucide-vue-next'
  import { useAuthStore } from '@/stores/auth'

  const authStore = useAuthStore()
  const initials = authStore.initials
  const firstName = authStore.firstName

  defineProps({
    username: {
      type: String,
      default: 'User'
    }
  })

  const favorites = [
    {
      name: 'Home',
      icon: Home,
      color: '#6d8f62'
    },
    {
      name: 'Work Documents',
      icon: Folder,
      color: '#803f7f'
    },
    {
      name: 'Photography',
      icon: Camera,
      color: '#e97777'
    }
  ]

  const sharedWithMe = [
    {
      name: 'Project Flycatcher',
      icon: Folder,
      color: '#6d8f62'
    },
    {
      name: 'McDermon Financial',
      icon: Folder,
      color: '#803f7f'
    }
  ]
</script>

<template>
  <div class="commander-sidebar">
    <CommanderSidebarSection title="Favorites" :items="favorites" />
    <CommanderSidebarSection title="Shared with me" :items="sharedWithMe" class="mt-5" />
    <CommanderSidebarFooter :username="firstName" />
  </div>
</template>

<style scoped>
  .commander-sidebar {
    width: 300px;
    height: calc(100% - 20px);
    background: linear-gradient(to bottom, #ffffff35, #fff);
    border-radius: 10px;
    filter: drop-shadow(3px 3px 10px rgba(0, 0, 0, 0.08));
    z-index: 10;
    margin-left: 10px;
    margin-top: 10px;
    margin-bottom: 10px;
    padding-top: 20px;
    padding-left: 10px;
    padding-right: 10px;
    padding-bottom: 10px;
  }
</style>
