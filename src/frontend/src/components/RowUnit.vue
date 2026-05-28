<template>
  <tr @click="toggle(unit.id)"
      class="tree-unit"
      :class="`level-${level}`"
  >
    <td :style="{ paddingLeft: `${12 + level * 10}px`, textAlign: 'left' }">
      {{ index }}
    </td>
    <td class="nested-name-cell" :style="{ paddingLeft: `${12 + level * 10}px`, textAlign: 'left' }">
      <div class="indent-wrapper">
        <span 
          v-if="(unit.children && unit.children.length) || (unit.personnel && unit.personnel.length)" 
          class="chevron-icon"
          :class="{ 'is-expanded': expanded.has(unit.id) }"
          @click.stop="toggle(unit.id)"
        >
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
        </span>
        <span>{{ unit.name }}</span>
      </div>
    </td>
    <td>{{ unit.totalShtat !== undefined ? unit.totalShtat : (unit.shtat || 0) }}</td>
    <td>{{ unit.totalPersonnelCount !== undefined ? unit.totalPersonnelCount : (unit.personnel?.length || 0) }}</td>
    
    <!-- Только реальные статусы, без "Налицо" (id=1) -->
    <td v-for="id in localStatusOrder" :key="id" class="status-cell">
      {{ countStatusRecursive(unit, id) }}
    </td>

    <td v-if="showNoteColumn"></td>
  </tr>

  <template v-if="expanded.has(unit.id)">
    <tr v-for="(p, i) in unit.personnel" 
        :key="p.id"
        class="personnel-row"
        :class="`level-${level}`"
    >
      <td :style="{ paddingLeft: `${12 + (level + 1) * 10}px`, textAlign: 'left' }">
        {{ index }}<span style="opacity: 0.5">.{{ i + 1 }}</span>
      </td>
      <td class="nested-name-cell"
          colspan="3"
          :style="{ paddingLeft: `${12 + (level + 1) * 10}px`, textAlign: 'left' }"
          :title="!showNoteColumn ? p.note : null"
      >
        <div class="indent-wrapper">
          <span style="margin-right: 8px; opacity: 0.4; font-weight: bold;">·</span>
          <span>{{ p.last_name }} {{ p.first_name }} {{ p.middle_name }}</span>

          <span v-if="!showNoteColumn && p.note" class="note-icon" :title="p.note">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
              <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
          </span>
        </div>
      </td>
      
      <td v-for="id in localStatusOrder"
          :key="id"
          class="status-cell"
          :class="{ 'editable': canEdit(id, p) }"
          @click.stop="setStatus(p, id)"
      >
        <span v-if="p.current_status_id === id" style="color: #4ade80;">✔</span>
      </td>

      <td v-if="showNoteColumn" class="note-cell" style="cursor: text;">
        <input v-if="editingPersonId === p.id"
               type="text"
               v-model="editNoteText"
               v-focus
               @blur="finishEditingNote(p)"
               @keyup.enter="finishEditingNote(p)"
               @keyup.esc="editingPersonId = null"
               class="inline-input"
        />
        <div v-else @click.stop="startEditingNote(p)" style="min-height: 20px; width: 100%;">
          {{ p.note }}
        </div>
      </td>
    </tr>

    <template v-if="!unit.children || unit.children.length === 0">
      <tr v-for="v in Math.max(0, Number(unit.shtat ?? unit.totalShtat ?? 0) - (unit.personnel?.length ?? 0))" 
          :key="'vacant-' + v"
          class="vacant-row"
      >
        <td :style="{ paddingLeft: `${12 + (level + 1) * 10}px`, textAlign: 'left' }">
          {{ index }}<span style="opacity: 0.5">.{{ (unit.personnel?.length || 0) + v }}</span>
        </td>
        <td :colspan="4 + localStatusOrder.length + (showNoteColumn ? 1 : 0)"
            class="vacant-text"
            style="text-align: center;"
        >
          ВАКАНТ
        </td>
      </tr>
    </template>

    <template v-for="(child, i) in unit.children" :key="child.id">
      <RowUnit :unit="child"
               :level="level + 1"
               :index="`${index}.${i + 1}`"
               :expanded="expanded"
               @toggle="$emit('toggle', $event)"
      />
    </template>
  </template>
</template>

<script setup>
import { inject, ref } from 'vue'
import { statusOrder } from '../constants/statuses'

const showNoteColumn = inject('showNoteColumn', false)
const getRank = inject('getRank')
const countStatusRecursive = inject('countStatusRecursive')
const allowedStatuses = inject('allowedStatuses', ref([]))
const lockedRowStatuses = inject('lockedRowStatuses', ref([]))
const updateStatus = inject('updateStatus', () => {})
const saveNote = inject('saveNote', () => {})

// Создаем локальную переменную с правильным порядком статусов
const localStatusOrder = statusOrder

const emit = defineEmits(['toggle'])
const editingPersonId = ref(null)
const editNoteText = ref("")

function startEditingNote(person) {
  editingPersonId.value = person.id
  editNoteText.value = person.note || ""
}

function finishEditingNote(person) {
  if (editingPersonId.value === person.id) {
    saveNote(person, editNoteText.value)
    editingPersonId.value = null
  }
}

const vFocus = { mounted: (el) => el.focus() }

defineProps(['unit', 'level', 'index', 'expanded'])

function toggle(id) { 
  emit('toggle', id) 
}

function canEdit(statusId, person) {
  if (lockedRowStatuses.value.includes(person.current_status_id)) return false;
  return allowedStatuses.value.includes(statusId);
}

function setStatus(person, statusId) {
  if (!canEdit(statusId, person)) return;
  const BASE_STATUS_ID = 1; 

  if (person.current_status_id === statusId) {
    if (statusId !== BASE_STATUS_ID) {
      updateStatus(person, BASE_STATUS_ID);
    }
  } else {
    updateStatus(person, statusId);
  }
}
</script>