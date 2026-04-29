<template>
  <div class="tree-select">
    <div class="select-box" @click="toggle">
      {{ selectedLabel || 'Выберите подразделение' }}
    </div>

    <div v-if="open" class="dropdown">
      <TreeNode
          v-for="node in options"
          :key="node.id"
          :node="node"
          :level="0"
          :selectedId="modelValue"
          @select="select"
      />
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

function toggle() {
  open.value = !open.value
}

function select(node) {
  emit('update:modelValue', node.id)
  open.value = false
}

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
}

.select-box {
  padding: 8px;
  border-radius: 8px;
  background: rgba(0,0,0,0.3);
  cursor: pointer;
}

.dropdown {
  position: absolute;
  width: 100%;
  margin-top: 6px;
  background: #0e2d21;
  border-radius: 10px;
  padding: 6px 0;
  max-height: 300px;
  overflow: auto;
  z-index: 10;
}
</style>