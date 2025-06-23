<template>

    <Head title="Submit a Claim" />
    <div class="max-w-6xl w-full mx-auto px-10 py-8 bg-white rounded-xl shadow-lg">
      <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Submit a Claim</h2>

      <form @submit.prevent="submit" class="space-y-6">
        <div>
          <label for="insurer_code" class="block text-sm font-medium text-gray-700 mb-1">Insurer Code</label>
          <div class="flex flex-col sm:flex-row items-center gap-3">
            <input
              id="insurer_code"
              v-model="form.insurer_code"
              type="text"
              class="input-field flex-1"
              placeholder="Type insurer code (e.g., ABN)"
              required
            />

            <span class="text-gray-500 text-sm hidden sm:block">OR</span>

            <select
              v-model="selectedInsurerId"
              @change="onInsurerSelect"
              class="input-field w-full sm:w-56 lg:w-64 flex-none pr-10"
            >
              <option value="" disabled selected>Select from list</option>
              <option v-for="insurer in insurers" :key="insurer.id" :value="insurer.id">
                {{ insurer.name }} ({{ insurer.code }})
              </option>
            </select>
          </div>
        </div>

        <div>
          <label for="provider_name" class="block text-sm font-medium text-gray-700 mb-1">Provider Name</label>
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
          <label for="encounter_date" class="block text-sm font-medium text-gray-700 mb-1">Encounter Date</label>
          <input
            id="encounter_date"
            v-model="form.encounter_date"
            type="date"
            class="input-field"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Claim Items</label>

          <div
            v-for="(item, index) in form.items"
            :key="index"
            class="grid grid-cols-1 md:grid-cols-item-row gap-3 mb-3 p-3 bg-gray-50 rounded-lg border border-gray-200"
          >
            <input
              v-model="item.name"
              type="text"
              class="input-field col-span-full md:col-span-2"
              placeholder="Item name"
              required
            />
           <div class="flex flex-col">
  <label class="text-sm font-medium text-gray-700 mb-1" :for="`unit_price_${index}`">Unit Price</label>
  <input
    :id="`unit_price_${index}`"
    v-model.number="item.unit_price"
    @input="validateUnitPrice(index)"
    type="number"
    min="0"
                class="input-field"
                placeholder="Price"
                required
            />
            </div>

            <div class="flex flex-col">
            <label class="text-sm font-medium text-gray-700 mb-1" :for="`quantity_${index}`">Quantity</label>
            <input
                :id="`quantity_${index}`"
                v-model.number="item.quantity"
                @input="validateQuantity(index)"
                type="number"
                min="0"
                class="input-field"
                placeholder="Qty"
                required
            />
            </div>
            <div class="flex items-center justify-between col-span-full md:col-span-1">
              <span class="text-sm font-medium text-gray-800">
                {{ formatCurrency(item.unit_price * item.quantity) }}
              </span>
              <button
                type="button"
                @click="removeItem(index)"
                class="text-red-500 hover:text-red-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50 rounded-full p-1"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm6 0a1 1 0 11-2 0v6a1 1 0 112 0V8z" clip-rule="evenodd" />
                </svg>
              </button>
            </div>
          </div>

          <button
            type="button"
            class="text-blue-600 hover:text-blue-800 text-sm mt-2 flex items-center gap-1 transition-colors duration-200"
            @click="addItem"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Add Item
          </button>
        </div>

        <div class="font-semibold text-xl text-right pt-4 border-t border-gray-200 mt-6">
          Total: <span class="text-blue-700">{{ formatCurrency(totalAmount) }}</span>
        </div>

        <div>
          <label for="specialty" class="block text-sm font-medium text-gray-700 mb-1">Specialty</label>
          <select id="specialty" v-model="form.specialty" class="input-field" required>
            <option disabled value="">Select specialty</option>
            <option>Cardiology</option>
            <option>Orthopedics</option>
            <option>Neurology</option>
            <option>Oncology</option>
          </select>
        </div>

        <div>
          <label for="priority_level" class="block text-sm font-medium text-gray-700 mb-1">Priority Level</label>
          <select id="priority_level" v-model="form.priority_level" class="input-field" required>
            <option disabled value="">Select priority</option>
            <option v-for="level in 5" :key="level" :value="level">{{ level }}</option>
          </select>
        </div>

        <div class="pt-4">
          <button
            type="submit"
            :disabled="submitting"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-3 rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50"
          >
            {{ submitting ? 'Submitting...' : 'Submit Claim' }}
          </button>
        </div>
      </form>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

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
  if (form.value.items[index].quantity < 0) {
    form.value.items[index].quantity = 0;
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

    // Optional: reset form or redirect
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
  @apply px-4 py-2 border border-gray-300 rounded-lg shadow-sm
    focus:border-blue-500 focus:ring-blue-500 focus:outline-none transition-all
    duration-200 text-gray-800;
  display: block;
  width: 100%;
}

.flex .input-field.flex-1 {
  width: auto;
}
/* This specific rule ensures that the select element gets enough padding for its arrow */
.flex select.input-field.flex-none {
  width: auto; /* Already set, but good to be explicit */
  /* Add specific padding to account for the native dropdown arrow */
  padding-right: 2.5rem; /* Equivalent to pr-10, gives space for the arrow */
  background-position: right 0.75rem center; /* Adjust arrow position if needed */
  background-size: 0.75rem; /* Adjust arrow size if needed */
  -webkit-appearance: none; /* Hide default arrow for more control */
  -moz-appearance: none;
  appearance: none; /* Hide default arrow for more control */
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23374151' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
  background-repeat: no-repeat;
}


@media (min-width: 768px) {
  .grid-cols-item-row {
    grid-template-columns: 2fr 1fr 0.8fr 1.5fr;
  }
}
</style>