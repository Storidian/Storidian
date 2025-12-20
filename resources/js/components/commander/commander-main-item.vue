<script setup>
  import { computed, ref, watch } from 'vue'
  import { getItemIcon } from '@/utils/filetypeIcons.js'

  const props = defineProps({
    item: {
      type: Object,
      required: true
    }
  })

  const emit = defineEmits(['select', 'unselect', 'item-click'])

  // Ref to the root element for marquee selection detection
  const itemElement = ref(null)

  // Selection state
  const selected = ref(false)

  // Get the appropriate icon for this item
  const icon = computed(() => getItemIcon(props.item))

  // Click handler - delegates to parent for selection logic
  const onClick = e => {
    emit('item-click', { item: props.item, shiftKey: e.shiftKey })
  }

  // Selection methods
  const select = () => {
    selected.value = true
  }

  const unselect = () => {
    selected.value = false
  }

  const isSelected = () => {
    return selected.value
  }

  // Expose methods and refs for parent component
  defineExpose({
    select,
    unselect,
    isSelected,
    itemElement
  })

  // Emit events when selection changes
  watch(selected, newVal => {
    emit(newVal ? 'select' : 'unselect', props.item)
  })
</script>

<template>
  <div ref="itemElement" class="commander-main-item" :class="{ selected }" @click="onClick">
    <div class="commander-main-item-thumbnail" v-if="item.thumbnail">
      <img :src="'/images/dev_icon_tests/' + item.thumbnail" />
    </div>
    <component :is="icon" class="commander-main-item-icon" v-else />
    <span class="commander-main-item-name">{{ item.name }}</span>
  </div>
</template>

<style scoped>
  .commander-main-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 10px;
    border-radius: 10px;
    aspect-ratio: 1/1;
    cursor: pointer;
    transition: all 0.3s ease;
    user-select: none;

    .commander-main-item-icon {
      width: 48px;
      height: 48px;
      color: #666;
      transition: all 0.3s ease;
    }

    .commander-main-item-name {
      font-size: 0.85rem;
      color: #4d4d4d;
      text-align: center;
      word-break: break-word;
      transition: all 0.3s ease;
    }

    .commander-main-item-thumbnail {
      width: 48px;
      height: 48px;
      border-radius: 10px;
      background-color: white;
      overflow: hidden;

      display: flex;
      align-items: center;
      justify-content: center;
    }

    .commander-main-item-thumbnail img {
      max-width: 100%;
      max-height: 100%;
      width: auto;
      height: auto;
      object-fit: contain;
      border-radius: 10px;
    }

    &:hover {
      background: linear-gradient(to bottom, #f8f8f8, #fff);
    }

    &.selected {
      background: linear-gradient(to bottom, #d34225, #d34225);

      .commander-main-item-icon {
        color: #fff;
      }

      .commander-main-item-name {
        color: #fff;
      }
    }
  }
</style>
