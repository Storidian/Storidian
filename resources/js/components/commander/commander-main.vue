<script setup>
  import { ref } from 'vue'
  import CommanderMainItem from './commander-main-item.vue'

  const items = [
    {
      name: 'Documents',
      path: '/documents',
      type: 'folder'
    },
    {
      name: 'Photos',
      path: '/photos',
      type: 'folder'
    },
    {
      name: 'Music',
      path: '/music',
      type: 'folder'
    },
    {
      name: 'Projects',
      path: '/projects',
      type: 'folder'
    },
    {
      name: 'Videos',
      path: '/videos',
      type: 'folder'
    },
    {
      name: 'File.txt',
      path: '/file.txt',
      type: 'file',
      filetype: 'txt'
    },
    {
      name: 'File.pdf',
      path: '/file.pdf',
      type: 'file',
      filetype: 'pdf'
    },
    {
      name: 'File.docx',
      path: '/file.docx',
      type: 'file',
      filetype: 'docx'
    }
  ]

  const itemRefs = ref([])

  const onContainerClick = (e) => {
    // Only deselect if clicking directly on the container, not on an item
    if (e.target.classList.contains('commander-main-items')) {
      unselectAllItems()
    }
  }

  const unselectAllItems = () => {
    itemRefs.value.forEach(itemRef => {
      if (itemRef) {
        itemRef.unselect()
      }
    })
  }
</script>

<template>
  <div class="commander-main">
    <div class="commander-main-items" @click="onContainerClick">
      <CommanderMainItem 
        v-for="(item, index) in items" 
        :key="item.name" 
        :item="item"
        :ref="el => itemRefs[index] = el"
      />
    </div>
  </div>
</template>

<style scoped>
  .commander-main {
    flex: 1;
    padding: 10px;

    .commander-main-items {
      display: grid;
      grid-template-columns: repeat(auto-fill, 100px);
      align-content: start;
      gap: 10px;
      padding: 20px;
      padding-top: 80px;
      height: 100%;
    }
  }
</style>
