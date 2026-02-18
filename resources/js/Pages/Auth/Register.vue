<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <div class="space-y-6">
            <h2 class="text-2xl font-bold text-white mb-8 tracking-tight text-center">Create Account</h2>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <InputLabel for="name" value="Name" class="text-slate-400 font-bold uppercase text-[10px] tracking-widest mb-2" />

                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full bg-slate-950/50 border-slate-800 text-white rounded-2xl focus:ring-blue-500 focus:border-blue-500"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Your full name"
                    />

                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="email" value="Email" class="text-slate-400 font-bold uppercase text-[10px] tracking-widest mb-2" />

                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full bg-slate-950/50 border-slate-800 text-white rounded-2xl focus:ring-blue-500 focus:border-blue-500"
                        v-model="form.email"
                        required
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
                        autocomplete="new-password"
                        placeholder="Create a strong password"
                    />

                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div>
                    <InputLabel
                        for="password_confirmation"
                        value="Confirm Password"
                        class="text-slate-400 font-bold uppercase text-[10px] tracking-widest mb-2"
                    />

                    <TextInput
                        id="password_confirmation"
                        type="password"
                        class="mt-1 block w-full bg-slate-950/50 border-slate-800 text-white rounded-2xl focus:ring-blue-500 focus:border-blue-500"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Repeat your password"
                    />

                    <InputError
                        class="mt-2"
                        :message="form.errors.password_confirmation"
                    />
                </div>

                <div class="pt-4">
                    <PrimaryButton
                        class="w-full justify-center py-4 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-2xl transition-all shadow-lg shadow-blue-600/20 uppercase tracking-widest text-xs"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Register
                    </PrimaryButton>
                </div>

                <p class="text-center text-xs text-slate-500 font-medium pt-2">
                    Already have an account? 
                    <Link :href="route('login')" class="text-blue-400 hover:text-blue-300 font-bold ml-1">Log in</Link>
                </p>
            </form>
        </div>
    </GuestLayout>
</template>
