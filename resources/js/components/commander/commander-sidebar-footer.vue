<script setup>
  import CommanderSidebarFooterItem from './commander-sidebar-footer-item.vue'
  import { User, Settings } from 'lucide-vue-next'
</script>

<template>
  <div class="commander-sidebar-footer">
    <CommanderSidebarFooterItem label="Dean" :icon="User" />
    <CommanderSidebarFooterItem label="Settings" :icon="Settings" />
  </div>
</template>

<style scoped>
  .commander-sidebar-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    color: #fff;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    padding: 10px;
  }
</style>

