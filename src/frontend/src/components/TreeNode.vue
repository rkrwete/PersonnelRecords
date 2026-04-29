<template>
  <div>
    <div
        class="node"
        :style="{ paddingLeft: `${level * 16}px` }"
    >
      <span v-if="hasChildren" @click.stop="toggle">
        {{ open ? '▼' : '▶' }}
      </span>

      <span
          class="label"
          :class="{ active: node.id === selectedId }"
          @click="selectNode"
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
          @select="$emit('select', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  node: Object,
  level: Number,
  selectedId: [String, Number]
})

const emit = defineEmits(['select'])

const open = ref(false)

const hasChildren = computed(() =>
    props.node.children && props.node.children.length
)

function toggle() {
  open.value = !open.value
}

function selectNode() {
  emit('select', props.node)
}
</script>

<style scoped>
.node {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 6px;
  cursor: pointer;
}

.label {
  flex: 1;
}

.node span {
  padding-left: 10px;
}

.label.active {
  color: #4ade80;
}
</style>