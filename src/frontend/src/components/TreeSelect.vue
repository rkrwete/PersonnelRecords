<template>
  <div class="tree-select">
    <div class="select-box" @click="toggle">
      {{ selectedLabel || 'Выберите подразделение' }}
      <span class="select-arrow">{{ open ? '▲' : '▼' }}</span>
    </div>

    <div v-if="open" class="dropdown">
      <div class="search-wrapper">
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Поиск подразделения..." 
          class="search-input"
          @click.stop
        />
      </div>

      <template v-if="filteredOptions.length > 0">
        <TreeNode
            v-for="node in filteredOptions"
            :key="node.id"
            :node="node"
            :level="0"
            :selectedId="modelValue"
            :is-searching="!!searchQuery" 
            @select="select"
        />
      </template>
      <div v-else class="no-results">
        Ничего не найдено
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import TreeNode from './TreeNode.vue'

const props = defineProps({
  options: Array,
  modelValue: [String, Number]
})

const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const searchQuery = ref('')

function toggle() {
  open.value = !open.value
  if (!open.value) searchQuery.value = ''
}

function select(node) {
  emit('update:modelValue', node.id)
  open.value = false
  searchQuery.value = ''
}

function filterTree(nodes, query) {
  if (!query) return nodes
  const cleanQuery = query.toLowerCase().trim()

  return nodes.reduce((acc, node) => {
    const matchingChildren = node.children ? filterTree(node.children, query) : []
    const isMatch = node.name.toLowerCase().includes(cleanQuery)

    if (isMatch || matchingChildren.length > 0) {
      acc.push({
        ...node,
        children: matchingChildren.length > 0 ? matchingChildren : (node.children ? [] : undefined)
      })
    }
    return acc
  }, [])
}

const filteredOptions = computed(() => {
  return filterTree(props.options || [], searchQuery.value)
})

function findLabel(nodes, id) {
  for (const n of nodes) {
    if (n.id === id) return n.name
    if (n.children) {
      const found = findLabel(n.children, id)
      if (found) return found
    }
  }
  return null
}

const selectedLabel = computed(() =>
    findLabel(props.options || [], props.modelValue)
)
</script>

<style scoped>
.tree-select {
  position: relative;
  width: 100%;
  max-width: 400px; /* Ограничиваем ширину селектора, чтобы он не растягивался на всю карту */
  margin: 0 auto 15px auto; /* Центрируем по дизайну страницы */
  font-size: 13px;
}

.select-box {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 14px;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: rgba(255, 255, 255, 0.9);
  cursor: pointer;
  transition: background 0.15s ease, border-color 0.15s ease;
}

.select-box:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.2);
}

.select-arrow {
  font-size: 9px;
  opacity: 0.5;
}

.dropdown {
  position: absolute;
  width: 100%;
  margin-top: 6px;
  background: #0e2d21; /* Базовый глубокий цвет вашей системы */
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 6px 0;
  max-height: 260px;
  overflow-y: auto;
  z-index: 100;
  backdrop-filter: blur(50px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
}

.search-wrapper {
  padding: 4px 10px 10px 10px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  margin-bottom: 6px;
}

.search-input {
  width: 100%;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #fff;
  padding: 6px 10px;
  border-radius: 6px;
  outline: none;
  font-size: 13px;
  box-sizing: border-box;
  transition: border-color 0.15s ease;
}

.search-input:focus {
  border-color: rgba(255, 255, 255, 0.4);
  background: rgba(255, 255, 255, 0.12);
}

.no-results {
  padding: 16px;
  text-align: center;
  color: rgba(255, 255, 255, 0.4);
}
</style>