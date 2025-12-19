<script setup>
  import { ref, computed } from 'vue'
  import CommanderMainItem from './commander-main-item.vue'
  import { useMarqueeSelection } from '@/composables/useMarqueeSelection.js'

  // Mock data - will be replaced with real data from API/store
  const items = [
    { name: 'Documents', path: '/documents', type: 'folder' },
    { name: 'Photos', path: '/photos', type: 'folder' },
    { name: 'Music', path: '/music', type: 'folder' },
    { name: 'Projects', path: '/projects', type: 'folder' },
    { name: 'Videos', path: '/videos', type: 'folder' },
    { name: 'File.txt', path: '/file.txt', type: 'file', filetype: 'txt' },
    { name: 'File.pdf', path: '/file.pdf', type: 'file', filetype: 'pdf' },
    { name: 'File.docx', path: '/file.docx', type: 'file', filetype: 'docx' }
  ]

  // Refs
  const containerRef = ref(null)
  const itemRefs = ref([])

  // Track selected items reactively
  const selectedItemNames = ref(new Set())

  // Computed: array of selected item objects
  const selectedItems = computed(() => {
    return items.filter(item => selectedItemNames.value.has(item.name))
  })

  // Computed: count of selected items
  const selectedCount = computed(() => selectedItemNames.value.size)

  // Handle item selection changes
  const onItemSelect = (item) => {
    selectedItemNames.value = new Set([...selectedItemNames.value, item.name])
  }

  const onItemUnselect = (item) => {
    const newSet = new Set(selectedItemNames.value)
    newSet.delete(item.name)
    selectedItemNames.value = newSet
  }

  // Marquee selection
  const {
    isSelecting,
    marqueeStyle,
    onMouseDown,
    onMouseMove,
    onMouseUp,
    onContainerClick,
    onItemClick
  } = useMarqueeSelection({
    containerRef,
    itemRefs,
    getItemElement: (ref) => ref?.itemElement,
    containerClass: 'commander-main-items'
  })

  // Wrap onItemClick to pass items
  const handleItemClick = (payload) => {
    onItemClick(payload, items)
  }
</script>

<template>
  <div class="commander-main">
    <!-- Selection status -->
    <div v-if="selectedCount > 0" class="selection-status">
      {{ selectedCount }} item{{ selectedCount !== 1 ? 's' : '' }} selected
    </div>

    <div 
      ref="containerRef"
      class="commander-main-items" 
      :class="{ 'is-selecting': isSelecting }"
      @click="onContainerClick"
      @mousedown="onMouseDown"
      @mousemove="onMouseMove"
      @mouseup="onMouseUp"
      @mouseleave="onMouseUp"
    >
      <CommanderMainItem 
        v-for="(item, index) in items" 
        :key="item.name" 
        :item="item"
        :ref="el => itemRefs[index] = el"
        @item-click="handleItemClick"
        @select="onItemSelect"
        @unselect="onItemUnselect"
      />
      
      <!-- Marquee selection rectangle -->
      <div 
        v-if="isSelecting" 
        class="marquee-selection"
        :style="marqueeStyle"
      />
    </div>
  </div>
</template>

<style scoped>
  .commander-main {
    flex: 1;
    padding: 10px;
    position: relative;

    .selection-status {
      position: absolute;
      bottom: 20px;
      right: 20px;
      padding: 10px 30px;
      background: linear-gradient(to bottom, #ffffff35, #fff);
      color: #2f2f2f;
      border-radius: 40px;
      font-size: 1.2rem;
      filter: drop-shadow(3px 3px 10px rgba(0, 0, 0, 0.08));
      z-index: 50;
    }

    .commander-main-items {
      position: relative;
      display: grid;
      grid-template-columns: repeat(auto-fill, 100px);
      align-content: start;
      gap: 10px;
      padding: 20px;
      padding-top: 80px;
      height: 100%;

      &.is-selecting {
        user-select: none;
        cursor: crosshair;
      }
    }

    .marquee-selection {
      position: absolute;
      background: rgba(211, 66, 37, 0.15);
      border: 1px solid rgba(211, 66, 37, 0.6);
      pointer-events: none;
      z-index: 100;
    }
  }
</style>
