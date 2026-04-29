<template>
  <div class="node"
       @mouseenter="onEnter"
       @mouseleave="onLeave"
  >
    <div
        class="unit"
        :class="{ active: selected?.id === node.id }"
        @click="emit('select', node)"
    >
      {{ node.name }}
    </div>

    <div v-if="open && hasChildren" class="children">
      <UnitNode
          v-for="c in node.children"
          :key="c.id"
          :node="c"
          :selected="selected"
          @select="$emit('select', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

defineOptions({
  name: 'UnitNode'
})

const props = defineProps({
  node: Object,
  selected: Object
})

const emit = defineEmits(['select'])

const open = ref(false)

const hasChildren = computed(() => {
  return props.node.children && props.node.children.length > 0
})

function toggle() {
  emit('select', props.node)

  if (hasChildren.value) {
    open.value = !open.value
  }
}

let timeout

function onEnter() {
  clearTimeout(timeout)
  if (hasChildren.value) open.value = true
}

function onLeave() {
  timeout = setTimeout(() => {
    open.value = false
  }, 150)
}
</script>

<style scoped>
.node {
  position: relative;
}
.unit {
  position: relative;
  padding: 10px;
  border-radius: 10px;
  cursor: pointer;
  transition: 0.2s;
  user-select: none;
}

.unit:hover {
  background: rgba(255,255,255,0.1);
}

.unit.active {
  background: rgba(255,255,255,0.2);
}

.children {
  position: absolute;
  top: 0;
  left: 100%;
  min-width: 220px;
  background: rgba(0,0,0,0.8);
  backdrop-filter: blur(14px);
  border-radius: 12px;
  z-index: 100;
}
</style>