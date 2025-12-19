import { ref, computed } from 'vue'

/**
 * Composable for marquee (rubber band) selection functionality
 * @param {Object} options
 * @param {Ref} options.containerRef - Ref to the container element
 * @param {Ref} options.itemRefs - Ref to array of item component refs
 * @param {Function} options.getItemElement - Function to get DOM element from item ref
 * @param {String} options.containerClass - CSS class name of the container (for click detection)
 */
export function useMarqueeSelection({
  containerRef,
  itemRefs,
  getItemElement = (ref) => ref?.itemElement,
  containerClass = 'commander-main-items'
}) {
  // Marquee state
  const isSelecting = ref(false)
  const selectionStart = ref({ x: 0, y: 0 })
  const selectionEnd = ref({ x: 0, y: 0 })
  const isAdditiveSelection = ref(false)
  const justFinishedDrag = ref(false)

  // Computed style for the marquee rectangle
  const marqueeStyle = computed(() => {
    const left = Math.min(selectionStart.value.x, selectionEnd.value.x)
    const top = Math.min(selectionStart.value.y, selectionEnd.value.y)
    const width = Math.abs(selectionEnd.value.x - selectionStart.value.x)
    const height = Math.abs(selectionEnd.value.y - selectionStart.value.y)
    
    return {
      left: `${left}px`,
      top: `${top}px`,
      width: `${width}px`,
      height: `${height}px`
    }
  })

  // Check if two rectangles intersect
  const rectsIntersect = (rect1, rect2) => {
    return !(
      rect1.right < rect2.left ||
      rect1.left > rect2.right ||
      rect1.bottom < rect2.top ||
      rect1.top > rect2.bottom
    )
  }

  // Get mouse position relative to container
  const getRelativePosition = (e) => {
    const containerRect = containerRef.value.getBoundingClientRect()
    return {
      x: e.clientX - containerRect.left,
      y: e.clientY - containerRect.top
    }
  }

  // Update item selection based on marquee intersection
  const updateSelectionFromMarquee = () => {
    if (!containerRef.value) return

    const containerRect = containerRef.value.getBoundingClientRect()
    
    const marqueeRect = {
      left: containerRect.left + Math.min(selectionStart.value.x, selectionEnd.value.x),
      right: containerRect.left + Math.max(selectionStart.value.x, selectionEnd.value.x),
      top: containerRect.top + Math.min(selectionStart.value.y, selectionEnd.value.y),
      bottom: containerRect.top + Math.max(selectionStart.value.y, selectionEnd.value.y)
    }

    itemRefs.value.forEach(itemRef => {
      const element = getItemElement(itemRef)
      if (!itemRef || !element) return
      
      const itemRect = element.getBoundingClientRect()
      
      if (rectsIntersect(marqueeRect, itemRect)) {
        itemRef.select()
      } else if (!isAdditiveSelection.value) {
        itemRef.unselect()
      }
    })
  }

  // Unselect all items
  const unselectAll = () => {
    itemRefs.value.forEach(itemRef => {
      if (itemRef) {
        itemRef.unselect()
      }
    })
  }

  // Mouse event handlers
  const onMouseDown = (e) => {
    if (!e.target.classList.contains(containerClass)) return
    
    const pos = getRelativePosition(e)
    
    isAdditiveSelection.value = e.shiftKey
    
    if (!isAdditiveSelection.value) {
      unselectAll()
    }
    
    isSelecting.value = true
    selectionStart.value = pos
    selectionEnd.value = pos
  }

  const onMouseMove = (e) => {
    if (!isSelecting.value) return
    
    selectionEnd.value = getRelativePosition(e)
    updateSelectionFromMarquee()
  }

  const onMouseUp = () => {
    if (isSelecting.value) {
      const dx = Math.abs(selectionEnd.value.x - selectionStart.value.x)
      const dy = Math.abs(selectionEnd.value.y - selectionStart.value.y)
      if (dx > 3 || dy > 3) {
        justFinishedDrag.value = true
      }
    }
    isSelecting.value = false
  }

  const onContainerClick = (e) => {
    if (justFinishedDrag.value) {
      justFinishedDrag.value = false
      return
    }
    
    if (e.target.classList.contains(containerClass)) {
      unselectAll()
    }
  }

  // Handle individual item clicks
  const onItemClick = ({ item, shiftKey }, items) => {
    const clickedIndex = items.findIndex(i => i.name === item.name)
    const clickedRef = itemRefs.value[clickedIndex]
    
    if (!clickedRef) return
    
    if (shiftKey) {
      if (clickedRef.isSelected()) {
        clickedRef.unselect()
      } else {
        clickedRef.select()
      }
    } else {
      unselectAll()
      clickedRef.select()
    }
  }

  return {
    // State
    isSelecting,
    marqueeStyle,
    
    // Methods
    unselectAll,
    onMouseDown,
    onMouseMove,
    onMouseUp,
    onContainerClick,
    onItemClick
  }
}

