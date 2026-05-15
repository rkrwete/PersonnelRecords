<style scoped>
.tree-unit {
  cursor: pointer;
  font-weight: 500;
}

.level-0 td { background: transparent; }
.level-1 td { background: rgba(255,255,255,0.02); }
.level-2 td { background: rgba(255,255,255,0.04); }
.level-3 td { background: rgba(255,255,255,0.06); }

th,
td {
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 10px 0;
  vertical-align: middle;
  text-align: center;
}

th {
  background: rgba(255, 255, 255, 0.05);
  font-weight: 600;
}

tr {
  transition: background 0.15s ease;
}

tr:hover {
  background: rgba(255, 255, 255, 0.04);
}

td:nth-child(n+3),
th:nth-child(n+3) {
  text-align: center;
}

.status-cell {
  text-align: center;
  transition: background 0.15s ease;
}

.status-cell.editable {
  cursor: pointer;
}

.status-cell.editable:hover {
  background: rgba(255, 255, 255, 0.15);
}

.vacant-text {
  font-family: "Tektur", sans-serif;
  font-weight: 700;
  letter-spacing: 1px;
  pointer-events: none;
  color: rgba(255, 255, 255, 0.5);
}

.inline-input {
  width: 100%;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid var(--btn);
  color: #fff;
  padding: 4px 8px;
  border-radius: 6px;
  outline: none;
  font-family: inherit;
  font-size: 12px;
}

.inline-input:focus {
  background: rgba(255, 255, 255, 0.15);
  border-color: var(--text-hover);
}

.note-icon {
  margin-left: 8px;
  opacity: 0.7;
  cursor: help;
  width: 16px;
  height: 16px;
  vertical-align: middle;
  transition: opacity 0.5s ease;
}

.note-icon:hover {
  opacity: 1;
}

.nested-name-cell {
  position: relative;
}

svg {
  width: 15px;
}
</style>

<template>
  <tr @click="toggle(unit.id)"
      class="tree-unit"
      :class="`level-${level}`"
  >
    <td :style="{ paddingLeft: `${5 + level * 10}px`, textAlign: 'left' }">
      {{ index }}
    </td>
    <td
        class="nested-name-cell"
        :style="{ paddingLeft: `${5 + level * 10}px`, textAlign: 'left' }"
    >
      {{ unit.name }}
    </td>
    <td>{{ unit.totalShtat !== undefined ? unit.totalShtat : (unit.shtat || 0) }}</td>
    <td>{{ unit.totalPersonnelCount !== undefined ? unit.totalPersonnelCount : (unit.personnel?.length || 0) }}</td>
    <td v-for="id in statusOrder" :key="id">
      {{ countStatusRecursive(unit, id) }}
    </td>
    <td v-if="showNoteColumn"></td>
  </tr>

  <template v-if="expanded.has(unit.id)">
    <tr v-for="(p, i) in unit.personnel" :key="p.id">
      <td :style="{ paddingLeft: `${5 + (level + 1) * 10}px`, textAlign: 'left' }">
        {{ index }}.{{ i + 1 }}
      </td>
      <td
          class="nested-name-cell"
          colspan="3"
          :style="{ paddingLeft: `${5 + (level + 1) * 10}px`, textAlign: 'left' }"
          :title="!showNoteColumn ? p.note : null"
      >
        {{ getRank(p.rank_id) }} {{ p.last_name }} {{ p.first_name }} {{ p.middle_name }}

        <span v-if="!showNoteColumn && p.note" class="note-icon" :title="p.note">
          <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
          >
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
          </svg>
        </span>
      </td>
      <td
          v-for="id in statusOrder"
          :key="id"
          class="status-cell"
          :class="{ 'editable': canEdit(id, p) }"
          @click="setStatus(p, id)"
      >
        <span v-if="p.current_status_id === id">✔</span>
      </td>

      <td v-if="showNoteColumn" class="note-cell" style="cursor: text;">
        <input
            v-if="editingPersonId === p.id"
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
      <tr v-for="v in Math.max(0, Number(unit.shtat ?? unit.totalShtat ?? 0) - (unit.personnel?.length ?? 0))" :key="'vacant-' + v">
        <td :style="{ paddingLeft: `${5 + (level + 1) * 10}px`, textAlign: 'left' }">
          {{ index }}.{{ (unit.personnel?.length || 0) + v }}
        </td>
        <td
            :colspan="3 + statusOrder.length + (showNoteColumn ? 1 : 0)"
            class="vacant-text"
            style="text-align: center;"
        >
          ВАКАНТ
        </td>
      </tr>
    </template>

    <template v-for="(child, i) in unit.children" :key="child.id">
      <RowUnit
          :unit="child"
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
  updateStatus(person, statusId);
}
</script>