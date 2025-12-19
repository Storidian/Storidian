<script setup>
  import { ChevronRight, Home, User } from 'lucide-vue-next'

  defineProps({
    username: {
      type: String,
      required: true
    },
    currentFolder: {
      type: String,
      required: true
    }
  })
</script>

<template>
  <h1 class="commander-title">
    <User class="commander-title-icon" />
    {{ username }}
    <ChevronRight class="commander-title-separator" />
    <Home class="commander-title-icon" />
    {{ currentFolder }}
  </h1>
</template>

<style scoped>
  .commander-title {
    background: linear-gradient(to bottom, #ffffff35, #fff);
    width: fit-content;
    padding: 10px 30px;
    font-size: 1.2rem;
    border-radius: 40px;
    filter: drop-shadow(3px 3px 10px rgba(0, 0, 0, 0.08));
    color: #2f2f2f;
    position: absolute;
    top: 20px;
    left: 340px;
    z-index: 11;

    .commander-title-separator {
      display: inline-block;
      color: #999;
      margin-left: 2px;
      margin-right: 2px;
      transform: translateY(-2px);
    }

    .commander-title-icon {
      display: inline-block;
      color: #999;
      margin-left: 2px;
      margin-right: 2px;
      transform: translateY(-3px);
      width: 20px;
    }
  }
</style>

