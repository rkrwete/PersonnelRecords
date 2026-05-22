<template>
  <div>
    <div
        class="node"
        :style="{ paddingLeft: `${level * 16 + 10}px` }"
        @click="selectNode"
    >
      <span 
        v-if="hasChildren" 
        class="chevron-icon" 
        :class="{ 'is-expanded': open }"
        @click.stop="toggle"
      >
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
      </span>
      <span v-else class="chevron-spacer"></span>

      <span
          class="label"
          :class="{ active: node.id === selectedId }"
      >
        {{ node.name }}
      </span>
    </div>

    <div v-if="open">
      <TreeNode
          v-for="child in node.children"
          :key="child.id"
          :node="child"
          :level="level + 1"
          :selectedId="selectedId"
          :is-searching="isSearching" 
          @select="$emit('select', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  node: Object,
  level: Number,
  selectedId: [String, Number],
  isSearching: Boolean // Новый пропс: идет ли поиск прямо сейчас
})

const emit = defineEmits(['select'])
const open = ref(false)

const hasChildren = computed(() =>
    props.node.children && props.node.children.length
)

// Измененная магия раскрытия:
watch(
  () => props.node,
  (newNode) => {
    // Раскрываем ветку ТОЛЬКО если пользователь действительно что-то ищет
    if (props.isSearching && newNode.children && newNode.children.length > 0) {
      open.value = true
    } else if (!props.isSearching) {
      // Если поиск очищен или еще не начался — возвращаем дерево в закрытое состояние
      open.value = false
    }
  },
  { deep: true, immediate: true }
)

function toggle() {
  open.value = !open.value
}

function selectNode() {
  emit('select', props.node)
}
</script>

<style scoped>
/* Стили остаются прежними */
.node {
  display: flex;
  align-items: center;
  padding: 6px 12px;
  cursor: pointer;
  transition: background 0.1s ease;
  color: rgba(255, 255, 255, 0.8);
}
.node:hover {
  background: rgba(255, 255, 255, 0.06);
  color: #fff;
}
.chevron-icon {
  margin-right: 8px;
  transition: transform 0.15s ease;
  opacity: 0.5;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 12px;
  height: 12px;
}
.chevron-icon.is-expanded {
  transform: rotate(90deg);
  opacity: 0.9;
}
.chevron-spacer {
  display: inline-block;
  width: 20px;
}
.label {
  flex: 1;
  text-overflow: ellipsis;
  overflow: hidden;
  white-space: nowrap;
}
.label.active {
  color: #4ade80;
  font-weight: 600;
}
</style>