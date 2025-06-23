<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const userType = ref('');

const selectUserType = (type) => {
  userType.value = type;
  
  if (type === 'provider') {
    router.visit('/submit-claim');
  } else if (type === 'insurer') {
    router.visit('/insurer-dashboard');
  }
};
</script>

<template>
    <Head title="Welcome - Claims Management System" />
    
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
        <div class="max-w-4xl w-full">
            <!-- Header -->
            <div class="text-center mb-12">
                <div class="flex justify-center mb-6">
                    <div class="bg-blue-600 p-4 rounded-full">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
                <h1 class="text-4xl font-bold text-gray-900 mb-4">Claims Management System</h1>
                <p class="text-xl text-gray-600">Welcome! Please select your role to continue.</p>
            </div>

            <!-- User Type Selection -->
            <div class="grid md:grid-cols-2 gap-8 max-w-2xl mx-auto">
                <!-- Provider Card -->
                <div 
                    @click="selectUserType('provider')"
                    class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 cursor-pointer transform hover:scale-105 border-2 border-transparent hover:border-blue-500"
                >
                    <div class="p-8 text-center">
                        <div class="bg-green-100 p-4 rounded-full w-20 h-20 mx-auto mb-6 flex items-center justify-center">
                            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-semibold text-gray-900 mb-3">Healthcare Provider</h3>
                        <p class="text-gray-600 mb-6">Submit and manage medical claims for your patients</p>
                        <div class="bg-green-50 text-green-700 px-4 py-2 rounded-lg text-sm font-medium">
                            Submit Claims
                        </div>
                    </div>
                </div>

                <!-- Insurer Card -->
                <div 
                    @click="selectUserType('insurer')"
                    class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 cursor-pointer transform hover:scale-105 border-2 border-transparent hover:border-blue-500"
                >
                    <div class="p-8 text-center">
                        <div class="bg-blue-100 p-4 rounded-full w-20 h-20 mx-auto mb-6 flex items-center justify-center">
                            <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-semibold text-gray-900 mb-3">Insurance Company</h3>
                        <p class="text-gray-600 mb-6">Review and process submitted claims</p>
                        <div class="bg-blue-50 text-blue-700 px-4 py-2 rounded-lg text-sm font-medium">
                            View Dashboard
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-12 text-gray-500">
                <p>&copy; 2025 Claims Management System. All rights reserved.</p>
            </div>
        </div>
    </div>
</template>
