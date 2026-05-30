<script setup>
import { onMounted, onBeforeUnmount, ref, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api.js'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import {
  Chart, BarController, BarElement, CategoryScale, LinearScale,
  Tooltip, Legend
} from 'chart.js'

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend)

const router = useRouter()
const loading = ref(true)
const stats = ref(null)
const chartCanvas = ref(null)
let chart = null

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/analytics')
    stats.value = data?.data ?? null
  } catch (_e) {
    stats.value = null
  } finally {
    loading.value = false
    await nextTick()
    renderChart()
  }
})

onBeforeUnmount(() => {
  if (chart) chart.destroy()
})

watch(stats, () => renderChart())

function renderChart () {
  if (!chartCanvas.value || !stats.value) return
  if (chart) chart.destroy()
  chart = new Chart(chartCanvas.value, {
    type: 'bar',
    data: {
      labels: ['Pending', 'Reviewed', 'Accepted', 'Rejected'],
      datasets: [{
        label: 'Applications',
        data: [
          stats.value.pending_count,
          stats.value.reviewed_count,
          stats.value.accepted_count,
          stats.value.rejected_count
        ],
        backgroundColor: ['#F59E0B', '#3B82F6', '#10B981', '#EF4444'],
        borderRadius: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
  })
}
</script>

<template>
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Admin dashboard</h1>
        <p class="text-sm text-slate-500">Overview of postings and applications.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button class="btn-primary" @click="router.push('/admin/jobs')">Post new job</button>
        <button class="btn-secondary" @click="router.push('/admin/applications')">View applications</button>
      </div>
    </header>

    <LoadingSpinner v-if="loading" />

    <div v-else-if="!stats" class="card p-12 text-center text-slate-500">
      <p>Could not load analytics.</p>
    </div>

    <div v-else>
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <div class="card p-5">
          <p class="text-sm text-slate-500">Active Listings</p>
          <p class="text-3xl font-semibold text-slate-900 mt-1">{{ stats.active_jobs }}</p>
          <p class="text-xs text-slate-400 mt-1">of {{ stats.total_jobs }} total</p>
        </div>
        <div class="card p-5">
          <p class="text-sm text-slate-500">Total Applications</p>
          <p class="text-3xl font-semibold text-slate-900 mt-1">{{ stats.total_applications }}</p>
          <p class="text-xs text-slate-400 mt-1">{{ stats.applications_this_week }} this week</p>
        </div>
        <div class="card p-5">
          <p class="text-sm text-slate-500">Pending</p>
          <p class="text-3xl font-semibold text-amber-600 mt-1">{{ stats.pending_count }}</p>
        </div>
        <div class="card p-5">
          <p class="text-sm text-slate-500">Accepted</p>
          <p class="text-3xl font-semibold text-emerald-600 mt-1">{{ stats.accepted_count }}</p>
        </div>
        <div class="card p-5">
          <p class="text-sm text-slate-500">Rejected</p>
          <p class="text-3xl font-semibold text-red-600 mt-1">{{ stats.rejected_count }}</p>
        </div>
      </div>

      <div class="card mt-6 p-5">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Application status breakdown</h2>
        <div class="h-72">
          <canvas ref="chartCanvas"></canvas>
        </div>
      </div>
    </div>
  </section>
</template>
