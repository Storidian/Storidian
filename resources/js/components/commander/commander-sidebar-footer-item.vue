<script setup>
  defineProps({
    label: {
      type: String,
      required: true
    },
    icon: {
      type: Object,
      required: true
    }
  })
</script>

<template>
  <div class="commander-sidebar-footer-item">
    <component :is="icon" class="commander-sidebar-footer-item-icon" />
    <span class="commander-sidebar-footer-item-text">{{ label }}</span>
  </div>
</template>

<style scoped>
  .commander-sidebar-footer-item {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    background: linear-gradient(to bottom, #1f1f1f, #1f1f1f);
    padding: 10px 20px;
    border-radius: 10px;
    cursor: pointer;
    width: 100%;

    &:hover {
      background: linear-gradient(to bottom, #2f2f2f, #2f2f2f);
    }
  }

  .commander-sidebar-footer-item-icon {
    display: inline-block;
    color: #fff;
    margin-right: 10px;
    width: 20px;
    transform: translateY(-1px);
  }

  .commander-sidebar-footer-item-text {
    font-weight: 400;
    color: #fff;
  }
</style>

