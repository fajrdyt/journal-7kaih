<template>
  <span :class="['status-badge', badgeClass]">
    <span class="status-dot"></span>
    {{ displayLabel }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: {
    type: String,
    default: 'pending',
  },
  label: {
    type: String,
    default: '',
  },
})

const statusMap = {
  'not-checked-in': {
    label: 'Belum Check-in',
    className: 'neutral',
  },
  'checked-in': {
    label: 'Sudah Check-in',
    className: 'info',
  },
  pending: {
    label: 'Menunggu Validasi',
    className: 'warning',
  },
  partial: {
    label: 'Sebagian Tervalidasi',
    className: 'warning',
  },
  complete: {
    label: 'Validasi Selesai',
    className: 'success',
  },
  validated: {
    label: 'Tervalidasi',
    className: 'success',
  },
  done: {
    label: 'Sudah Dilakukan',
    className: 'success',
  },
  'not-done': {
    label: 'Belum Dilakukan',
    className: 'neutral',
  },
}

const currentStatus = computed(() => {
  return statusMap[props.status] || statusMap.pending
})

const badgeClass = computed(() => currentStatus.value.className)

const displayLabel = computed(() => {
  return props.label || currentStatus.value.label
})
</script>

<style scoped>
.status-badge {
  min-height: 27px;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 0 10px;
  border: 1px solid transparent;
  border-radius: 999px;
  font-size: 10.5px;
  line-height: 1;
  font-weight: 800;
  white-space: nowrap;
}

.status-dot {
  width: 6px;
  height: 6px;
  flex-shrink: 0;
  border-radius: 50%;
  background: currentColor;
}

.status-badge.neutral {
  border-color: #e2e8f0;
  background: #f8fafc;
  color: #64748b;
}

.status-badge.info {
  border-color: #bfdbfe;
  background: #eff6ff;
  color: #1686d1;
}

.status-badge.warning {
  border-color: #fed7aa;
  background: #fff7ed;
  color: #c2410c;
}

.status-badge.success {
  border-color: #bbf7d0;
  background: #ecfdf5;
  color: #047857;
}
</style>
