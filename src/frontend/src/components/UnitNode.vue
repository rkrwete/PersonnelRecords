<template>
  <div class="node" @mouseenter="onEnter" @mouseleave="onLeave">
    <div
        class="unit"
        :class="{ active: selected?.id === node.id }"
        @click="$emit('select', node)"
    >
      {{ node.name }}
    </div>
    
    <div v-if="open && hasChildren"
         class="children"
         :style="dropdownStyle">
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
const dropdownStyle = ref({})
let timeout

const hasChildren = computed(() => {
  return props.node.children && props.node.children.length > 0
})

function onEnter(e) {
  clearTimeout(timeout)
  if (!hasChildren.value) return

  const unitEl = e.currentTarget.querySelector('.unit')
  const rect = unitEl.getBoundingClientRect()

  dropdownStyle.value = {
    position: 'fixed',
    top: rect.top + 'px',
    left: (rect.right - 2) + 'px' // Нахлест в 2px, чтобы не было "мертвой зоны" при переводе мыши
  }

  open.value = true
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
    user-select: none;
    transition: background-color 0.5s ease, color 0.5s ease, transform 0.5s ease;
  }

  .unit.active {
    background: var(--btn);
  }

  .unit:hover {
    background: var(--btn-hover);
    color: var(--text-hover);
    transform: translateY(-2px);
  }

  .children {
    z-index: 9999;
    min-width: 220px;
    background: var(--bg, #2a2a2a); 
    border-radius: 10px;
    box-shadow: 2px 2px 5px 3px rgba(0, 0, 0, 0.3);
    padding: 5px 0;
  }
</style>