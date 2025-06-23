<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';

const insurers = ref([]);
const selectedInsurerId = ref('');
const claims = ref([]);
const loading = ref(false);
const claimsLoading = ref(false);
const optimizationData = ref(null);
const costAnalysis = ref(null);
const showOptimization = ref(false);
const showCostAnalysis = ref(false);

const fetchInsurers = async () => {
  try {
    const response = await fetch('/api/insurers');
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    insurers.value = await response.json();
  } catch (error) {
    console.error('Error fetching insurers:', error);
    insurers.value = [];
  }
};

const fetchClaims = async (insurerId) => {
  if (!insurerId) {
    claims.value = [];
    return;
  }

  claimsLoading.value = true;
  try {
    const response = await fetch(`/api/insurers/${insurerId}/claims`);
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    claims.value = await response.json();
  } catch (error) {
    console.error('Error fetching claims:', error);
    claims.value = [];
  } finally {
    claimsLoading.value = false;
  }
};

const fetchOptimizationRecommendations = async () => {
  if (!selectedInsurerId.value) return;
  
  try {
    const response = await fetch(`/api/insurers/${selectedInsurerId.value}/optimization-recommendations`);
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    optimizationData.value = await response.json();
  } catch (error) {
    console.error('Error fetching optimization data:', error);
    optimizationData.value = null;
  }
};

const fetchCostAnalysis = async () => {
  if (!selectedInsurerId.value) return;
  
  try {
    const response = await fetch(`/api/insurers/${selectedInsurerId.value}/cost-analysis`);
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    costAnalysis.value = await response.json();
  } catch (error) {
    console.error('Error fetching cost analysis:', error);
    costAnalysis.value = null;
  }
};

const triggerOptimization = async () => {
  if (!selectedInsurerId.value) return;
  
  try {
    const response = await fetch(`/api/insurers/${selectedInsurerId.value}/optimize-batching`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ date: new Date().toISOString().split('T')[0] })
    });
    
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    const result = await response.json();
    
    alert(`Optimization completed! ${result.result.optimization_notes.join(', ')}`);
    
    // Refresh data
    await fetchClaims(selectedInsurerId.value);
    await fetchOptimizationRecommendations();
  } catch (error) {
    console.error('Error triggering optimization:', error);
    alert('Error triggering optimization: ' + error.message);
  }
};

const onInsurerSelect = () => {
  fetchClaims(selectedInsurerId.value);
  fetchOptimizationRecommendations();
  fetchCostAnalysis();
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
  }).format(value);
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const getPriorityColor = (priority) => {
  const colors = {
    1: 'bg-red-100 text-red-800',
    2: 'bg-orange-100 text-orange-800',
    3: 'bg-yellow-100 text-yellow-800',
    4: 'bg-blue-100 text-blue-800',
    5: 'bg-green-100 text-green-800'
  };
  return colors[priority] || 'bg-gray-100 text-gray-800';
};

const totalClaims = computed(() => claims.value.length);
const totalAmount = computed(() => claims.value.reduce((sum, claim) => sum + parseFloat(claim.total_amount), 0));

onMounted(() => {
  fetchInsurers();
});
</script>

<template>
    <Head title="Insurer Dashboard" />

    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Insurer Dashboard</h1>
                        <p class="text-gray-600 mt-1">Manage and optimize claim processing</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="/" class="text-blue-600 hover:text-blue-800 font-medium">
                            ← Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Insurer Selection -->
            <div class="bg-white rounded-lg shadow-sm border p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Select Insurer</h2>
                <div class="flex items-center space-x-4">
                    <select 
                        v-model="selectedInsurerId" 
                        @change="onInsurerSelect"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">Choose an insurer...</option>
                        <option v-for="insurer in insurers" :key="insurer.id" :value="insurer.id">
                            {{ insurer.name }} ({{ insurer.code }})
                        </option>
                    </select>
                </div>
            </div>

            <!-- Optimization Panel -->
            <div v-if="selectedInsurerId && optimizationData" class="bg-white rounded-lg shadow-sm border p-6 mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Optimization Recommendations</h2>
                    <button
                        @click="triggerOptimization"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                    >
                        Run Optimization
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-blue-600">Unbatched Claims</div>
                        <div class="text-2xl font-bold text-blue-900">{{ optimizationData.total_unbatched_claims || 0 }}</div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-green-600">Estimated Cost</div>
                        <div class="text-2xl font-bold text-green-900">{{ formatCurrency(optimizationData.estimated_total_cost || 0) }}</div>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-yellow-600">Avg Cost/Claim</div>
                        <div class="text-2xl font-bold text-yellow-900">{{ formatCurrency(optimizationData.average_cost_per_claim || 0) }}</div>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-purple-600">Capacity Used</div>
                        <div class="text-2xl font-bold text-purple-900">{{ optimizationData.capacity_utilization || 0 }}%</div>
                    </div>
                </div>

                <div v-if="optimizationData.suggestions && optimizationData.suggestions.length > 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-yellow-800 mb-2">Optimization Suggestions:</h3>
                    <ul class="text-sm text-yellow-700 space-y-1">
                        <li v-for="suggestion in optimizationData.suggestions" :key="suggestion" class="flex items-start">
                            <span class="mr-2">•</span>
                            {{ suggestion }}
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Cost Analysis Panel -->
            <div v-if="selectedInsurerId && costAnalysis" class="bg-white rounded-lg shadow-sm border p-6 mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Cost Analysis</h2>
                    <button
                        @click="showCostAnalysis = !showCostAnalysis"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        {{ showCostAnalysis ? 'Hide' : 'Show' }} Details
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-gray-600">Total Claims</div>
                        <div class="text-2xl font-bold text-gray-900">{{ costAnalysis.analysis.total_claims || 0 }}</div>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-red-600">Total Processing Cost</div>
                        <div class="text-2xl font-bold text-red-900">{{ formatCurrency(costAnalysis.analysis.total_processing_cost || 0) }}</div>
                    </div>
                    <div class="bg-indigo-50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-indigo-600">Avg Cost/Claim</div>
                        <div class="text-2xl font-bold text-indigo-900">{{ formatCurrency(costAnalysis.analysis.average_cost_per_claim || 0) }}</div>
                    </div>
                </div>

                <div v-if="showCostAnalysis" class="space-y-6">
                    <!-- Cost by Specialty -->
                    <div>
                        <h3 class="text-md font-medium text-gray-900 mb-3">Cost by Specialty</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="(data, specialty) in costAnalysis.analysis.cost_by_specialty" :key="specialty" class="bg-gray-50 p-3 rounded-lg">
                                <div class="text-sm font-medium text-gray-900">{{ specialty }}</div>
                                <div class="text-xs text-gray-600">{{ data.count }} claims</div>
                                <div class="text-sm font-semibold text-gray-900">{{ formatCurrency(data.total_cost) }}</div>
                                <div class="text-xs text-gray-500">{{ data.percentage }}% of total</div>
                            </div>
                        </div>
                    </div>

                    <!-- Cost by Priority -->
                    <div>
                        <h3 class="text-md font-medium text-gray-900 mb-3">Cost by Priority Level</h3>
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div v-for="(data, priority) in costAnalysis.analysis.cost_by_priority" :key="priority" class="bg-gray-50 p-3 rounded-lg">
                                <div class="text-sm font-medium text-gray-900">Priority {{ priority }}</div>
                                <div class="text-xs text-gray-600">{{ data.count }} claims</div>
                                <div class="text-sm font-semibold text-gray-900">{{ formatCurrency(data.total_cost) }}</div>
                                <div class="text-xs text-gray-500">{{ data.percentage }}% of total</div>
                            </div>
                        </div>
                    </div>

                    <!-- Optimization Opportunities -->
                    <div v-if="costAnalysis.analysis.optimization_opportunities && costAnalysis.analysis.optimization_opportunities.length > 0">
                        <h3 class="text-md font-medium text-gray-900 mb-3">Optimization Opportunities</h3>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <ul class="space-y-2">
                                <li v-for="opportunity in costAnalysis.analysis.optimization_opportunities" :key="opportunity.type" class="text-sm text-yellow-800">
                                    <span class="font-medium">{{ opportunity.recommendation }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Claims Summary -->
            <div v-if="selectedInsurerId" class="bg-white rounded-lg shadow-sm border p-6 mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-semibold text-gray-900">Claims Overview</h2>
                    <div class="flex items-center space-x-4">
                        <div class="text-sm text-gray-600">
                            Total Claims: <span class="font-semibold">{{ totalClaims }}</span>
                        </div>
                        <div class="text-sm text-gray-600">
                            Total Amount: <span class="font-semibold">{{ formatCurrency(totalAmount) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="claimsLoading" class="flex justify-center items-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                </div>

                <!-- Claims Table -->
                <div v-else-if="claims.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Provider</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Encounter Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Specialty</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="claim in claims" :key="claim.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ claim.provider.name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ formatDate(claim.encounter_date) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ claim.specialty }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getPriorityColor(claim.priority_level)}`">
                                        {{ claim.priority_level }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ claim.items.length }} items</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ formatCurrency(claim.total_amount) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ formatDate(claim.created_at) }}</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-8">
                    <div class="text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No claims found</h3>
                        <p class="mt-1 text-sm text-gray-500">Claims will appear here once they are submitted.</p>
                    </div>
                </div>
            </div>

            <!-- No Insurer Selected -->
            <div v-if="!selectedInsurerId" class="text-center py-12">
                <div class="text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Select an insurer</h3>
                    <p class="mt-1 text-sm text-gray-500">Choose an insurer from the dropdown above to view their claims and optimization data.</p>
                </div>
            </div>
        </div>
    </div>
</template>
