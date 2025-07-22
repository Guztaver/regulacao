<script setup lang="ts">
import PatientLookup from '@/components/PatientLookup.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Regulação List - Sistema de Gestão Médica">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div
        class="flex min-h-screen flex-col items-center bg-gradient-to-br from-blue-50 to-white p-6 text-gray-900 lg:justify-center lg:p-8 dark:from-gray-900 dark:to-gray-800 dark:text-gray-100"
    >
        <header class="mb-6 w-full max-w-[335px] text-sm lg:max-w-6xl">
            <nav class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600 text-white">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-gray-900 dark:text-white">Regulação List</span>
                </div>
                <div class="flex items-center justify-end gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="inline-block rounded-lg border border-blue-200 bg-blue-600 px-6 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 dark:border-blue-500 dark:bg-blue-600 dark:hover:bg-blue-700"
                    >
                        Acessar Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="inline-block rounded-lg border border-transparent px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            Entrar
                        </Link>
                        <Link
                            :href="route('register')"
                            class="inline-block rounded-lg border border-blue-200 bg-blue-600 px-6 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 dark:border-blue-500 dark:bg-blue-600 dark:hover:bg-blue-700"
                        >
                            Cadastrar
                        </Link>
                    </template>
                </div>
            </nav>
        </header>

        <div class="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow">
            <main class="flex w-full max-w-6xl flex-col items-center text-center">
                <!-- Hero Content -->
                <div class="mb-12 w-full space-y-8">
                    <div class="space-y-4">
                        <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl dark:text-white">
                            Sistema de
                            <span class="bg-gradient-to-r from-blue-600 to-green-600 bg-clip-text text-transparent"> Regulação Médica </span>
                        </h1>
                        <p class="text-xl text-gray-600 dark:text-gray-300">
                            Gerencie pacientes, procedimentos e documentos médicos de forma organizada e eficiente
                        </p>
                    </div>
                </div>

                <!-- Features and Demo Section -->
                <div class="mb-12 flex w-full flex-col items-center text-center lg:flex-row lg:items-center lg:text-left">
                    <!-- Features Content -->
                    <div class="flex-1 space-y-8 lg:pr-12">
                        <!-- Features Grid -->
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                            <div class="flex items-start space-x-3">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-300"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">Gestão de Pacientes</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Cadastro completo com informações de contato</p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-3">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">Controle de Procedimentos</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Acompanhe status de exames e consultas</p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-3">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-900 dark:text-purple-300"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">Documentos Médicos</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Upload seguro de prontuários e exames</p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-3">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100 text-orange-600 dark:bg-orange-900 dark:text-orange-300"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">Filtros Avançados</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Busca por datas, pacientes e códigos</p>
                                </div>
                            </div>
                        </div>

                        <!-- CTA Buttons -->
                        <div class="flex flex-col gap-4 sm:flex-row sm:justify-center lg:justify-start">
                            <Link
                                v-if="!$page.props.auth.user"
                                :href="route('register')"
                                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-8 py-3 text-base font-medium text-white transition-colors hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                            >
                                Começar Agora
                                <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </Link>
                            <Link
                                v-else
                                :href="route('dashboard')"
                                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-8 py-3 text-base font-medium text-white transition-colors hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                            >
                                Ir para Dashboard
                                <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </Link>
                            <a
                                href="https://github.com/guztaver/regulacao"
                                target="_blank"
                                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-8 py-3 text-base font-medium text-gray-700 transition-colors hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                Ver Documentação
                            </a>
                        </div>
                    </div>

                    <!-- Hero Image/Illustration -->
                    <div class="mt-12 flex-1 lg:mt-0">
                        <div class="relative">
                            <!-- Medical Dashboard Illustration -->
                            <div class="mx-auto max-w-lg rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-800">
                                <div class="mb-4 flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Dashboard Médico</h3>
                                    <div class="flex space-x-1">
                                        <div class="h-3 w-3 rounded-full bg-red-400"></div>
                                        <div class="h-3 w-3 rounded-full bg-yellow-400"></div>
                                        <div class="h-3 w-3 rounded-full bg-green-400"></div>
                                    </div>
                                </div>

                                <!-- Mock Dashboard Content -->
                                <div class="space-y-4">
                                    <!-- Stats Cards -->
                                    <div class="grid grid-cols-3 gap-3">
                                        <div class="rounded-lg bg-blue-50 p-3 text-center dark:bg-blue-900/20">
                                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">124</div>
                                            <div class="text-xs text-blue-700 dark:text-blue-300">Pacientes</div>
                                        </div>
                                        <div class="rounded-lg bg-green-50 p-3 text-center dark:bg-green-900/20">
                                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">89</div>
                                            <div class="text-xs text-green-700 dark:text-green-300">Ativos</div>
                                        </div>
                                        <div class="rounded-lg bg-purple-50 p-3 text-center dark:bg-purple-900/20">
                                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">35</div>
                                            <div class="text-xs text-purple-700 dark:text-purple-300">Concluídos</div>
                                        </div>
                                    </div>

                                    <!-- Patient List Mock -->
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-700">
                                            <div class="flex items-center space-x-3">
                                                <div class="h-8 w-8 rounded-full bg-gradient-to-r from-blue-400 to-blue-600"></div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Maria Silva</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">Email: maria.silva@email.com</div>
                                                </div>
                                            </div>
                                            <span
                                                class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200"
                                            >
                                                Ativo
                                            </span>
                                        </div>

                                        <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-700">
                                            <div class="flex items-center space-x-3">
                                                <div class="h-8 w-8 rounded-full bg-gradient-to-r from-purple-400 to-purple-600"></div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">João Santos</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">Email: joao.santos@email.com</div>
                                                </div>
                                            </div>
                                            <span
                                                class="rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                                            >
                                                Agendado
                                            </span>
                                        </div>

                                        <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-700">
                                            <div class="flex items-center space-x-3">
                                                <div class="h-8 w-8 rounded-full bg-gradient-to-r from-green-400 to-green-600"></div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Ana Costa</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">Email: ana.costa@email.com</div>
                                                </div>
                                            </div>
                                            <span
                                                class="rounded-full bg-purple-100 px-2 py-1 text-xs font-medium text-purple-800 dark:bg-purple-900 dark:text-purple-200"
                                            >
                                                Exame
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Floating Elements -->
                            <div
                                class="absolute -top-4 -right-4 h-12 w-12 rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 opacity-80 shadow-lg"
                            ></div>
                            <div
                                class="absolute -bottom-4 -left-4 h-8 w-8 rounded-lg bg-gradient-to-r from-green-500 to-green-600 opacity-60 shadow-lg"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Patient Lookup Section -->
                <div class="mx-auto w-full max-w-4xl">
                    <PatientLookup />
                </div>
            </main>
        </div>

        <!-- Footer -->
        <footer
            class="mt-12 w-full max-w-6xl border-t border-gray-200 pt-8 text-center text-sm text-gray-600 dark:border-gray-700 dark:text-gray-400"
        >
            <div class="flex flex-col items-center justify-between space-y-4 lg:flex-row lg:space-y-0">
                <div>
                    <p>&copy; 2025 Regulação List. Sistema de gestão médica para organização de pacientes e procedimentos.</p>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="#" class="hover:text-gray-900 dark:hover:text-gray-300">Documentação</a>
                    <a href="#" class="hover:text-gray-900 dark:hover:text-gray-300">Suporte</a>
                    <a href="#" class="hover:text-gray-900 dark:hover:text-gray-300">API</a>
                </div>
            </div>
        </footer>
    </div>
</template>
