<script setup>
  defineProps({
    name: {
      type: String,
      required: true
    },
    icon: {
      type: Object,
      required: true
    },
    color: {
      type: String,
      default: '#666'
    }
  })
</script>

<template>
  <div class="commander-sidebar-item">
    <div class="commander-sidebar-item-title">
      <component :is="icon" class="commander-sidebar-item-title-icon" :style="{ '--color': color }" />
      {{ name }}
    </div>
  </div>
</template>

<style scoped>
  .commander-sidebar-item {
    margin-bottom: 0px;
    border-radius: 10px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    padding-left: 10px;
    padding-right: 10px;
    padding-top: 5px;
    cursor: pointer;

    &:hover {
      background: linear-gradient(to bottom, #ffffffe6, #fff);
    }

    .commander-sidebar-item-title {
      font-size: 1rem;
      font-weight: 400;
      color: #4d4d4d;
    }

    .commander-sidebar-item-title-icon {
      display: inline-block;
      color: var(--color);
      margin-right: 10px;
      width: 20px;
      transform: translateY(-2px);
    }
  }
</style>

