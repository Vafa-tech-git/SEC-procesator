<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-sm font-medium text-emerald-400">
            {{ status }}
        </div>

        <div class="space-y-6">
            <h2 class="text-2xl font-bold text-white mb-8 tracking-tight text-center">Welcome Back</h2>
            
            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <InputLabel for="email" value="Email" class="text-slate-400 font-bold uppercase text-[10px] tracking-widest mb-2" />

                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full bg-slate-950/50 border-slate-800 text-white rounded-2xl focus:ring-blue-500 focus:border-blue-500"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="your@email.com"
                    />

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel for="password" value="Password" class="text-slate-400 font-bold uppercase text-[10px] tracking-widest mb-2" />

                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1 block w-full bg-slate-950/50 border-slate-800 text-white rounded-2xl focus:ring-blue-500 focus:border-blue-500"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />

                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center cursor-pointer group">
                        <Checkbox name="remember" v-model:checked="form.remember" class="rounded border-slate-700 bg-slate-950 text-blue-500 focus:ring-blue-500" />
                        <span class="ms-2 text-xs font-bold text-slate-500 group-hover:text-slate-300 transition-colors uppercase tracking-widest">Remember me</span>
                    </label>

                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs font-bold text-blue-400 hover:text-blue-300 transition-colors uppercase tracking-widest"
                    >
                        Forgot?
                    </Link>
                </div>

                <div class="pt-2">
                    <PrimaryButton
                        class="w-full justify-center py-4 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-2xl transition-all shadow-lg shadow-blue-600/20 uppercase tracking-widest text-xs"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Sign In
                    </PrimaryButton>
                </div>

                <p class="text-center text-xs text-slate-500 font-medium pt-4">
                    Don't have an account? 
                    <Link :href="route('register')" class="text-blue-400 hover:text-blue-300 font-bold ml-1">Register now</Link>
                </p>
            </form>
        </div>
    </GuestLayout>
</template>
