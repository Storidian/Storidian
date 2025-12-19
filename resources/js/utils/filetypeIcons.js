import { 
  Folder, 
  File, 
  FileText, 
  FileImage, 
  FileVideo, 
  FileAudio, 
  FileCode, 
  FileSpreadsheet, 
  FileArchive, 
  FileType 
} from 'lucide-vue-next'

/**
 * Mapping of file extensions to their corresponding icons
 */
export const filetypeIcons = {
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

/**
 * Get the appropriate icon for an item based on its type and filetype
 * @param {Object} item - The item object with type and optionally filetype
 * @returns {Component} The icon component to use
 */
export function getItemIcon(item) {
  if (item.type === 'folder') {
    return Folder
  }

  if (item.filetype && filetypeIcons[item.filetype]) {
    return filetypeIcons[item.filetype]
  }

  return File
}

