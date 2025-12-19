<script setup>
  import CommanderSidebarItem from './commander-sidebar-item.vue'

  defineProps({
    title: {
      type: String,
      required: true
    },
    items: {
      type: Array,
      required: true
    }
  })
</script>

<template>
  <div class="commander-sidebar-section">
    <h5 class="commander-sidebar-section-title">{{ title }}</h5>
    <div class="commander-sidebar-section-items">
      <CommanderSidebarItem
        v-for="item in items"
        :key="item.name"
        :name="item.name"
        :icon="item.icon"
        :color="item.color"
      />
    </div>
  </div>
</template>

<style scoped>
  .commander-sidebar-section {
    .commander-sidebar-section-title {
      font-size: 0.8rem;
      font-weight: 600;
      color: #848484;
      margin-bottom: 20px;
      margin-left: 15px;
    }

    .commander-sidebar-section-items {
      display: flex;
      flex-direction: column;
    }
  }
</style>

