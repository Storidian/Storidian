<script setup>
  import { ref } from 'vue'
  import { Folder, File } from 'lucide-vue-next'

  const active = ref(false)

  const onFocus = () => {
    active.value = true
  }

  const onBlur = () => {
    active.value = false
  }

  const results = ref([
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
      name: 'Product Shot.jpg',
      path: '/product-shot.jpg',
      type: 'file',
      filetype: 'jpg'
    },
    {
      name: 'Catalogue.pdf',
      path: '/catalogue.pdf',
      type: 'file',
      filetype: 'pdf'
    },
    {
      name: 'Company Report.docx',
      path: '/company-report.docx',
      type: 'file',
      filetype: 'docx'
    }
  ])
</script>

<template>
  <div class="commander-search-container">
    <div class="commander-search" :class="{ active: active }">
      <input class="commander-search-input" placeholder="Search anything..." @focus="onFocus" @blur="onBlur" />
    </div>
    <div class="commander-search-results" :class="{ visible: active }">
      <div class="commander-search-result-item" v-for="result in results" :key="result.name">
        <div class="commander-search-results-item-icon">
          <component :is="result.type === 'folder' ? Folder : File" />
        </div>
        <div class="commander-search-results-item-name">{{ result.name }}</div>
      </div>
    </div>
  </div>
</template>

<style scoped>
  .commander-search-container {
    --search-width: 300px;
    position: absolute;
    top: 20px;
    right: 20px;
  }

  .commander-search {
    width: fit-content;
    font-size: 1.2rem;
    border-radius: 40px;
    color: #2f2f2f;
    position: relative;
    z-index: 11;
    padding: 1px;
    background: linear-gradient(to bottom, #f5f5f5, #fff);
    filter: drop-shadow(3px 3px 10px rgba(0, 0, 0, 0.08));

    &::before {
      content: '';
      position: absolute;
      inset: 0;
      border-radius: 40px;
      background: radial-gradient(circle, #d34225, #2b7090);
      opacity: 0;
      transition: opacity 0.3s ease;
      width: calc(var(--search-width) + 2px);
    }

    &.active::before {
      opacity: 1;
    }

    .commander-search-input {
      position: relative;
      width: var(--search-width);
      height: 100%;
      border: none;
      outline: none;
      font-size: 1.2rem;
      color: #2f2f2f;
      padding: 10px 30px;
      background: linear-gradient(to bottom, #f5f5f5, #fff);
      border-radius: 38px;
      z-index: 11;
      box-sizing: border-box;
    }
  }

  .commander-search-results {
    position: absolute;
    top: 0;
    right: 0;
    background: linear-gradient(to bottom, #ffffff35, #fff);
    border-radius: 10px;
    filter: drop-shadow(3px 3px 10px rgba(0, 0, 0, 0.08));
    z-index: 10;
    padding: 10px;
    padding-top: 60px;
    height: fit-content;
    width: var(--search-width);
    box-sizing: border-box;

    opacity: 0;
    transform: translateY(-10px);
    transition: all 0.3s ease;

    &.visible {
      opacity: 1;
      transform: translateY(0);
    }

    .commander-search-result-item {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      gap: 10px;

      padding: 10px;
      border-radius: 10px;
      cursor: pointer;

      .commander-search-results-item-icon {
        width: 20px;
        height: 20px;
        color: #666;
        transform: translateY(-5px);
      }

      &:hover {
        filter: drop-shadow(3px 3px 10px rgba(0, 0, 0, 0.08));
        background: linear-gradient(to bottom, #d34225, #d34225);
        color: #fff;
        .commander-search-results-item-icon {
          color: #fff;
        }
        .commander-search-results-item-name {
          color: #fff;
        }
      }
    }
  }
</style>
