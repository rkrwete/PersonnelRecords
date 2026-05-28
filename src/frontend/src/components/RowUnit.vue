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
    <td v-for="id in statusOrder" :key="id">
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
      >
        <div class="indent-wrapper tooltip-container">
          <span style="margin-right: 8px; opacity: 0.4; font-weight: bold;">·</span>
          <span class="inline-rank">{{ getRank(p.rank_id) }}</span>
          <span class="person-name">{{ p.last_name }} {{ p.first_name }} {{ p.middle_name }}</span>
          <span v-if="!showNoteColumn && p.note" class="note-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
              <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
          </span>

          <div class="custom-tooltip">
            <div class="t-photo-wrap">
              <img v-if="p.photo" :src="p.photo" alt="Фото" class="t-photo" />
              <div v-else class="t-no-photo">Нет фото</div>
            </div>
            <div class="t-info">
              <div class="t-rank">{{ getRank(p.rank_id) }}</div>
              <div class="t-name">{{ p.last_name }} {{ p.first_name }} {{ p.middle_name }}</div>
              <div class="t-pos">{{ p.position?.title || 'Должность не указана' }}</div>
              <div class="t-note" v-if="!showNoteColumn && p.note">
                <span style="opacity:0.6;font-size:0.9em;">Примечание:</span> {{ p.note }}
              </div>
            </div>
          </div>
          </div>
      </td>
      <td v-for="id in statusOrder"
          :key="id"
          class="status-cell"
          :class="{ 'editable': canEdit(id, p) }"
          @click="setStatus(p, id)"
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
        <div v-else @click="startEditingNote(p)" style="min-height: 20px; width: 100%;">
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
        <td :colspan="3 + statusOrder.length + (showNoteColumn ? 1 : 0)"
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

const showNoteColumn = inject('showNoteColumn', false)
const statusOrder = inject('statusOrder')
const getRank = inject('getRank')
const countStatusRecursive = inject('countStatusRecursive')
const allowedStatuses = inject('allowedStatuses', ref([]))
const lockedRowStatuses = inject('lockedRowStatuses', ref([]))
const updateStatus = inject('updateStatus', () => {})
const saveNote = inject('saveNote', () => {})

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

function toggle(id) { emit('toggle', id) }
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

<style scoped>
.tree-unit {
  cursor: pointer;
  font-weight: 600;
}

.level-0 { background: rgba(255, 255, 255, 0.02); }
.level-1 { background: rgba(255, 255, 255, 0.04); }
.level-2 { background: rgba(255, 255, 255, 0.06); }
.level-3 { background: rgba(255, 255, 255, 0.08); }

.personnel-row {
  background: transparent !important;
}
.personnel-row td {
  color: rgba(255, 255, 255, 0.85);
  font-size: 0.95em;
}

.indent-wrapper {
  display: flex;
  align-items: center;
  position: relative;
}

.tooltip-container {
  padding-top: 2px;
  padding-bottom: 2px;
}

.custom-tooltip {
  position: absolute;
  bottom: calc(100% + 5px); 
  left: 20px; 
  background: #1e1e24;
  border: 1px solid rgba(255, 255, 255, 0.15);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.7);
  border-radius: 10px;
  padding: 12px;
  display: flex;
  gap: 12px;
  z-index: 1000;
  width: max-content;
  max-width: 360px;
  pointer-events: none;
  

  opacity: 0;
  visibility: hidden;
  transform: translateY(10px);
  transition: all 0.2s cubic-bezier(0.2, 0, 0, 1);
}

/* "Треугольник" указывающий вниз */
.custom-tooltip::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 20px;
  border-width: 7px;
  border-style: solid;
  border-color: #1e1e24 transparent transparent transparent;
}

.tooltip-container:hover .custom-tooltip {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.t-photo-wrap {
  flex-shrink: 0;
}

.t-photo {
  width: 70px;
  height: 90px;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.t-no-photo {
  width: 70px;
  height: 90px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.05);
  border: 1px dashed rgba(255, 255, 255, 0.3);
  border-radius: 6px;
  color: rgba(255, 255, 255, 0.4);
  font-size: 11px;
  text-align: center;
}

.t-info {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 4px;
}

.t-rank {
  font-size: 0.85em;
  color: rgba(255, 255, 255, 0.5);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.t-name {
  font-weight: 700;
  font-size: 1.1em;
  color: #ffffff;
}

.t-pos {
  font-size: 0.9em;
  color: rgba(255, 255, 255, 0.8);
  border-bottom: 1px solid rgba(255,255,255,0.1);
  padding-bottom: 4px;
}

.t-note {
  font-size: 0.85em;
  color: #fbbf24; /* Желтоватый цвет для акцента на заметке */
  margin-top: 2px;
  line-height: 1.2;
}
/* -------------------------------------- */

.chevron-icon {
  margin-right: 6px;
  transition: transform 0.2s ease;
  opacity: 0.6;
  flex-shrink: 0;
}
.chevron-icon.is-expanded {
  transform: rotate(90deg);
}

th, td {
  border: 1px solid rgba(255, 255, 255, 0.06);
  padding: 10px 8px;
  vertical-align: middle;
}

th {
  background: rgba(255, 255, 255, 0.07);
  font-weight: 600;
  text-align: center;
}

tr {
  transition: background 0.15s ease;
}

tr:hover {
  background: rgba(255, 255, 255, 0.1) !important;
}

td:nth-child(n+3), th:nth-child(n+3) {
  text-align: center;
}

.status-cell {
  text-align: center;
  transition: background 0.15s ease;
}
.status-cell.editable { cursor: pointer; }
.status-cell.editable:hover { background: rgba(255, 255, 255, 0.12); }

.vacant-row {
  background: rgba(244, 63, 94, 0.03); 
}
.vacant-text {
  font-family: "Tektur", sans-serif;
  font-weight: 700;
  letter-spacing: 2px;
  color: rgba(244, 63, 94, 0.6); 
}

.inline-input {
  width: 100%;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid var(--btn, #555);
  color: #fff;
  padding: 4px 8px;
  border-radius: 6px;
  outline: none;
  font-size: 12px;
}

.inline-input:focus {
  background: rgba(255, 255, 255, 0.15);
  border-color: var(--text-hover, #fff);
}

.note-icon {
  margin-left: 8px;
  opacity: 0.6;
  cursor: help;
  width: 14px;
  height: 14px;
  display: inline-flex;
  align-items: center;
}
.note-icon:hover { opacity: 1; }

svg { width: 100%; height: 100%; }


.inline-rank {
  width: 140px;          
  min-width: 140px;      
  flex-shrink: 0;          
  color: rgba(255, 255, 255, 0.45); 
  font-size: 0.9em;
  font-style: italic;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap; 
  padding-right: 10px;   
}

.person-name {
  color: rgba(255, 255, 255, 0.9);
  flex-grow: 1;          
}
</style>