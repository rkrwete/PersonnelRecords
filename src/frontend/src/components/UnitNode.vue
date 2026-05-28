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
    <div v-if="open && hasChildren"
         class="children"
         :style="dropdownStyle"
         @mouseenter="onDropdownEnter"
         @mouseleave="onDropdownLeave">
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
import { ref, computed, nextTick } from 'vue'

const isHoveringNode = ref(false)
const isHoveringDropdown = ref(false)
const dropdownStyle = ref({})

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

let timeout
let resizeObserver = null

function updateDropdownPosition(e) {
  const rect = e.currentTarget.getBoundingClientRect()
  
  dropdownStyle.value = {
    position: 'fixed',
    top: rect.top + 'px',
    left: (rect.right + 5) + 'px',
    zIndex: 1000
  }
}

function onEnter(e) {
  isHoveringNode.value = true
  if (hasChildren.value) {
    updateDropdownPosition(e)
    open.value = true
  }
}

function onLeave() {
  isHoveringNode.value = false
  scheduleClose()
}

function onDropdownEnter() {
  isHoveringDropdown.value = true
}

function onDropdownLeave() {
  isHoveringDropdown.value = false
  scheduleClose()
}

function scheduleClose() {
  clearTimeout(timeout)
  timeout = setTimeout(() => {
    if (!isHoveringNode.value && !isHoveringDropdown.value) {
      open.value = false
    }
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
    position: fixed;
    min-width: 220px;
    background: var(--bg);
    border-radius: 10px;
    z-index: 1000;
    box-shadow: 2px 2px 5px 3px rgba(0, 0, 0, 0.3);
    padding: 5px 0;
  }
</style>