<template>
    <Head title="Submit a Claim" />
    
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Submit a Claim</h1>
                        <p class="text-gray-600 mt-1">Submit medical claims for your patients</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="/" class="text-blue-600 hover:text-blue-800 font-medium">
                            ← Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-xl shadow-sm border">
                <!-- Form Header -->
                <div class="px-8 py-6 border-b border-gray-200">
                    <div class="flex items-center">
                        <div class="bg-blue-100 p-3 rounded-full">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-xl font-semibold text-gray-900">Claim Information</h2>
                            <p class="text-sm text-gray-600">Fill in the details below to submit your claim</p>
                        </div>
                    </div>
                </div>

                <!-- Form Content -->
                <form @submit.prevent="submit" class="p-8 space-y-8">
                    <!-- Insurer Selection Section -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Insurance Information</h3>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <label for="insurer_code" class="block text-sm font-medium text-gray-700 mb-2">
                                    Insurer Code
                                </label>
                                <input
                                    id="insurer_code"
                                    v-model="form.insurer_code"
                                    type="text"
                                    class="input-field"
                                    placeholder="e.g., INS-A"
                                    required
                                />
                            </div>
                            <div>
                                <label for="insurer_select" class="block text-sm font-medium text-gray-700 mb-2">
                                    Or Select from List
                                </label>
                                <select
                                    id="insurer_select"
                                    v-model="selectedInsurerId"
                                    @change="onInsurerSelect"
                                    class="input-field"
                                >
                                    <option value="" disabled selected>Choose an insurer</option>
                                    <option v-for="insurer in insurers" :key="insurer.id" :value="insurer.id">
                                        {{ insurer.name }} ({{ insurer.code }})
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Provider and Date Section -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <label for="provider_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Provider Name
                            </label>
                            <input
                                id="provider_name"
                                v-model="form.provider_name"
                                type="text"
                                class="input-field"
                                placeholder="e.g., General Hospital"
                                required
                            />
                        </div>
                        <div>
                            <label for="encounter_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Encounter Date
                            </label>
                            <input
                                id="encounter_date"
                                v-model="form.encounter_date"
                                type="date"
                                class="input-field"
                                required
                            />
                        </div>
                    </div>

                    <!-- Specialty and Priority Section -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <label for="specialty" class="block text-sm font-medium text-gray-700 mb-2">
                                Specialty
                            </label>
                            <select id="specialty" v-model="form.specialty" class="input-field" required>
                                <option disabled value="">Select specialty</option>
                                <option>Cardiology</option>
                                <option>Orthopedics</option>
                                <option>Neurology</option>
                                <option>Oncology</option>
                                <option>General Medicine</option>
                                <option>Pediatrics</option>
                                <option>Emergency Medicine</option>
                            </select>
                        </div>
                        <div>
                            <label for="priority_level" class="block text-sm font-medium text-gray-700 mb-2">
                                Priority Level
                            </label>
                            <select id="priority_level" v-model="form.priority_level" class="input-field" required>
                                <option disabled value="">Select priority</option>
                                <option v-for="level in 5" :key="level" :value="level">
                                    Level {{ level }} {{ level === 1 ? '(Highest)' : level === 5 ? '(Lowest)' : '' }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Claim Items Section -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-medium text-gray-900">Claim Items</h3>
                            <button
                                type="button"
                                @click="addItem"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add Item
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div
                                v-for="(item, index) in form.items"
                                :key="index"
                                class="bg-white rounded-lg border border-gray-200 p-6"
                            >
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-sm font-medium text-gray-900">Item {{ index + 1 }}</h4>
                                    <button
                                        v-if="form.items.length > 1"
                                        type="button"
                                        @click="removeItem(index)"
                                        class="text-red-500 hover:text-red-700 transition-colors duration-200"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Item Name</label>
                                        <input
                                            v-model="item.name"
                                            type="text"
                                            class="input-field"
                                            placeholder="e.g., Consultation Fee"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Unit Price</label>
                                        <input
                                            v-model.number="item.unit_price"
                                            @input="validateUnitPrice(index)"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="input-field"
                                            placeholder="0.00"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                                        <input
                                            v-model.number="item.quantity"
                                            @input="validateQuantity(index)"
                                            type="number"
                                            min="1"
                                            class="input-field"
                                            placeholder="1"
                                            required
                                        />
                                    </div>
                                </div>

                                <div class="mt-4 flex justify-end">
                                    <span class="text-sm font-medium text-gray-900">
                                        Subtotal: {{ formatCurrency(item.unit_price * item.quantity) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Total Amount -->
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-medium text-gray-900">Total Amount:</span>
                                <span class="text-2xl font-bold text-blue-600">{{ formatCurrency(totalAmount) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end pt-6">
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="inline-flex items-center px-8 py-3 bg-blue-600 text-white text-lg font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-lg hover:shadow-xl"
                        >
                            <svg v-if="submitting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            {{ submitting ? 'Submitting...' : 'Submit Claim' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';

const form = ref({
  insurer_code: '',
  provider_name: '',
  encounter_date: '',
  specialty: '',
  priority_level: '',
  items: [{ name: '', unit_price: 0, quantity: 1 }],
});

const submitting = ref(false);
const insurers = ref([]);
const selectedInsurerId = ref('');

const fetchInsurers = async () => {
  try {
    const response = await fetch('/api/insurers');
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    insurers.value = await response.json();

    if (form.value.insurer_code) {
      const matched = insurers.value.find(i => i.code === form.value.insurer_code);
      if (matched) {
        selectedInsurerId.value = matched.id;
      }
    }
  } catch (error) {
    console.error('Error fetching insurers:', error);
    insurers.value = [];
  }
};

onMounted(() => {
  fetchInsurers();
});

watch(() => form.value.insurer_code, (newCode) => {
  if (newCode) {
    const matched = insurers.value.find(i => i.code === newCode);
    if (matched && selectedInsurerId.value !== matched.id) {
      selectedInsurerId.value = matched.id;
    } else if (!matched && selectedInsurerId.value !== '') {
      selectedInsurerId.value = '';
    }
  } else if (selectedInsurerId.value !== '') {
    selectedInsurerId.value = '';
  }
});

const onInsurerSelect = () => {
  const selected = insurers.value.find(ins => ins.id === Number(selectedInsurerId.value));
  if (selected) {
    form.value.insurer_code = selected.code;
  } else {
    form.value.insurer_code = '';
  }
};

const addItem = () => {
  form.value.items.push({ name: '', unit_price: 0, quantity: 1 });
};

const removeItem = (index) => {
  form.value.items.splice(index, 1);
};

const validateUnitPrice = (index) => {
  if (form.value.items[index].unit_price < 0) {
    form.value.items[index].unit_price = 0;
  }
};

const validateQuantity = (index) => {
  if (form.value.items[index].quantity < 1) {
    form.value.items[index].quantity = 1;
  }
};

const totalAmount = computed(() =>
  form.value.items.reduce((sum, item) => sum + item.unit_price * item.quantity, 0)
);

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
  }).format(value);
};

const submit = async () => {
  if (form.value.items.length === 0) {
    alert('Please add at least one claim item.');
    return;
  }

  submitting.value = true;

  const payload = {
    ...form.value,
    submission_date: new Date().toISOString().split('T')[0], // "YYYY-MM-DD"
  };

  try {
    const response = await fetch('/api/claims', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify(payload),
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Failed to submit claim');
    }

    // Success - show success message and reset form
    alert('Claim submitted successfully!');
    form.value = {
      insurer_code: '',
      provider_name: '',
      encounter_date: '',
      specialty: '',
      priority_level: '',
      items: [{ name: '', unit_price: 0, quantity: 1 }],
    };
    selectedInsurerId.value = '';

  } catch (error) {
    console.error('Error submitting claim:', error);
    alert('Error: ' + error.message);
  } finally {
    submitting.value = false;
  }
};
</script>

<style scoped>
.input-field {
  @apply px-4 py-3 border border-gray-300 rounded-lg shadow-sm
    focus:border-blue-500 focus:ring-blue-500 focus:outline-none transition-all
    duration-200 text-gray-900 bg-white;
  display: block;
  width: 100%;
}

.input-field:focus {
  @apply ring-2 ring-blue-500 ring-opacity-50;
}

.input-field::placeholder {
  @apply text-gray-400;
}

/* Custom select styling */
select.input-field {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23374151' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
  background-position: right 0.75rem center;
  background-repeat: no-repeat;
  background-size: 0.75rem;
  padding-right: 2.5rem;
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
}
</style>