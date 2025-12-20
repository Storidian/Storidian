<script setup>
  import CommanderTitle from './commander-title.vue'
  import CommanderSearch from './commander-search.vue'
  import CommanderSidebar from './commander-sidebar.vue'
  import CommanderMain from './commander-main.vue'

  defineProps({
    username: {
      type: String,
      default: 'User'
    },
    currentFolder: {
      type: String,
      default: 'Home'
    }
  })
</script>

<template>
  <div class="commander">
    <CommanderTitle :username="username" :current-folder="currentFolder" />
    <CommanderSearch />
    <div class="commander-content">
      <CommanderSidebar :username="username" />
      <CommanderMain />
    </div>
  </div>
</template>

<style scoped>
  .commander {
    background-color: rgb(246, 246, 246);
    padding: 0px;
    border-radius: 18px;
    filter: drop-shadow(0 0 20px rgba(0, 0, 0, 0.1));
    height: 100%;

    .commander-content {
      display: flex;
      flex-direction: row;
      height: 100%;
    }
  }
</style>
