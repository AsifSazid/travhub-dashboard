<!-- This is for Client, Traveller, Vendor -->

<div class="bg-white rounded-lg shadow p-4 flex flex-col text-left">
    <div class="grid grid-cols-3 gap-4">
        <div class="col-span-2">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">All Credentials</h2>
            <div id="travellerList" class="grid grid-cols-4 gap-6 h-full mb-4">
                <div class="col-span-1">
                    <div class="bg-white rounded-xl shadow-lg">
                        <div class="flex justify-between items-start">
                            <div class="p-4">
                                <a href="#">
                                    <div class="flex justify-between items-start">
                                        <h3 class="text-lg text-gray-800"><strong>Asif M Sazid</strong></h3>
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </div>

                                    <p class="text-sm text-gray-600 mt-1">
                                        <Strong>URL: </Strong> A54187481
                                    </p>

                                    <p class="text-sm text-gray-600 mt-1">
                                        <Strong>@user: </Strong> Siblings
                                    </p>

                                    <p class="text-sm text-gray-600 mt-1">
                                        <Strong>Password: </Strong> Siblings
                                    </p>

                                    <p class="text-sm text-gray-600 mt-1">
                                        <Strong>Phone No: </Strong> 01751906710
                                    </p>

                                    <p class="text-sm text-gray-600 mt-1">
                                        <Strong>Note: </Strong> Not Applicable
                                    </p>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-span-1">
            <form class="max-w-2xl space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

                <h2 class="text-xl font-semibold text-gray-800">
                    Credentials Information
                </h2>

                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none"
                        placeholder="Credential name">
                </div>

                <!-- URL -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">URL</label>
                    <input type="url"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none"
                        placeholder="https://example.com">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none"
                        placeholder="email@example.com">
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="tel"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none"
                        placeholder="+8801XXXXXXXXX">
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none"
                        placeholder="••••••••">
                </div>

                <!-- Security Questions -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Security Questions
                    </label>

                    <div id="securityQuestions" class="space-y-3">
                        <input type="text"
                            name="security_questions[]"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none"
                            placeholder="Enter security question">
                    </div>

                    <button type="button"
                        onclick="addSecurityQuestion()"
                        class="mt-3 inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-700">
                        + Add another question
                    </button>
                </div>

                <!-- Note -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                    <textarea rows="3"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none"
                        placeholder="Additional notes..."></textarea>
                </div>

                <!-- Submit -->
                <div class="pt-4">
                    <button type="submit"
                        class="rounded-lg bg-blue-600 px-6 py-2 text-white font-medium hover:bg-blue-700 transition">
                        Save Credentials
                    </button>
                </div>

            </form>

            <script>
                function addSecurityQuestion() {
                    const container = document.getElementById('securityQuestions');

                    const input = document.createElement('input');
                    input.type = 'text';
                    input.name = 'security_questions[]';
                    input.placeholder = 'Enter security question';
                    input.className =
                        'w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none';

                    container.appendChild(input);
                }
            </script>

        </div>

    </div>

</div>