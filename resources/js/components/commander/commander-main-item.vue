<script setup>
  import { computed, ref, watch, defineEmits, defineExpose } from 'vue'
  import { Folder, File, FileText, FileImage, FileVideo, FileAudio, FileCode, FileSpreadsheet, FileArchive, FileType } from 'lucide-vue-next'

  const props = defineProps({
    item: {
      type: Object,
      required: true
    }
  })

  const filetypeIcons = {
    // Text documents
    txt: FileText,
    doc: FileText,
    docx: FileText,
    pdf: FileType,
    rtf: FileText,
    md: FileText,

    // Images
    jpg: FileImage,
    jpeg: FileImage,
    png: FileImage,
    gif: FileImage,
    svg: FileImage,
    webp: FileImage,

    // Video
    mp4: FileVideo,
    mov: FileVideo,
    avi: FileVideo,
    mkv: FileVideo,
    webm: FileVideo,

    // Audio
    mp3: FileAudio,
    wav: FileAudio,
    flac: FileAudio,
    aac: FileAudio,
    ogg: FileAudio,

    // Code
    js: FileCode,
    ts: FileCode,
    vue: FileCode,
    html: FileCode,
    css: FileCode,
    json: FileCode,
    php: FileCode,
    py: FileCode,

    // Spreadsheets
    xls: FileSpreadsheet,
    xlsx: FileSpreadsheet,
    csv: FileSpreadsheet,

    // Archives
    zip: FileArchive,
    rar: FileArchive,
    tar: FileArchive,
    gz: FileArchive,
    '7z': FileArchive
  }

  const icon = computed(() => {
    if (props.item.type === 'folder') {
      return Folder
    }

    if (props.item.filetype && filetypeIcons[props.item.filetype]) {
      return filetypeIcons[props.item.filetype]
    }

    return File
  })

  const selected = ref(false)

  const onClick = () => {
    if (selected.value) {
      unselect()
    } else {
      select()
    }
  }

  const select = () => {
    selected.value = true
  }

  const unselect = () => {
    selected.value = false
  }

  defineExpose({
    select,
    unselect
  })

  const emit = defineEmits(['select', 'unselect'])

  watch(selected, newVal => {
    if (newVal) {
      emit('select', props.item)
    } else {
      emit('unselect', props.item)
    }
  })
</script>

<template>
  <div class="commander-main-item" @click="onClick" :class="{ selected: selected }">
    <component :is="icon" class="commander-main-item-icon" />
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
