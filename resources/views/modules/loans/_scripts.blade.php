@push('scripts')
    <script>
        function loansPreview(init = {}) {
            const state = Object.assign({
                customerId: '',
                principal: '',
                interestRate: '2',
                method: 'FLAT',
                tenor: '',
                frequency: 'MONTHLY',
                disbursementDate: '',
                firstDueDate: '',
            }, init || {});

            return {
                ...state,

                preview: null,
                loading: false,
                error: null,
                timer: null,

                customers: @json($customers->map(fn ($c) => ['id' => (string) $c->id, 'name' => $c->full_name])->values()),

                get selectedCustomerName() {
                    const found = this.customers.find((c) => c.id === String(this.customerId));
                    return found ? found.name : '';
                },

                get previewPrincipal() {
                    return this.formatMoney(this.preview?.principal_amount ?? this.principal);
                },

                get previewTotalInterest() {
                    return this.formatMoney(this.preview?.total_interest ?? null);
                },

                get previewTotalPayable() {
                    return this.formatMoney(this.preview?.total_payable ?? null);
                },

                get previewInstallment() {
                    return this.formatMoney(this.preview?.installment_amount ?? null);
                },

                get previewInterest() {
                    const bps = this.preview?.interest_rate_bps;
                    if (bps !== undefined && bps !== null) {
                        return (bps / 100).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' % / periode';
                    }
                    return (this.interestRate || '') + ' % / periode';
                },

                get previewMethod() {
                    return this.method === 'FLAT' ? 'Flat (Tetap)' : 'Efektif Menurun';
                },

                get previewTenor() {
                    return this.tenor ? this.tenor + ' periode' : '-';
                },

                get previewFirstDue() {
                    return this.firstDueDate || '-';
                },

                get previewMaturity() {
                    return this.preview?.maturity_date || '-';
                },

                formatMoney(value) {
                    if (value === null || value === undefined || value === '') return '-';
                    const number = typeof value === 'number' ? value : (parseInt(value, 10) || 0);
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(number);
                },

                triggerPreview() {
                    clearTimeout(this.timer);
                    this.timer = setTimeout(() => this.fetchPreview(), 350);
                },

                async fetchPreview() {
                    const principal = parseInt(this.principal, 10) || 0;
                    const rate = parseFloat(this.interestRate) || 0;
                    const tenor = parseInt(this.tenor, 10) || 0;

                    if (principal < 1 || !rate || tenor < 1 || !this.firstDueDate) {
                        this.preview = null;
                        this.error = null;
                        return;
                    }

                    this.loading = true;
                    this.error = null;

                    try {
                        const response = await fetch('{{ route('loans.calculate') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            },
                            body: JSON.stringify({
                                principal_amount: principal,
                                interest_rate: rate,
                                interest_method: this.method,
                                tenor: tenor,
                                installment_frequency: this.frequency,
                                first_due_date: this.firstDueDate,
                            }),
                        });

                        const payload = await response.json();

                        if (!response.ok) {
                            const firstMessage = payload.errors ? Object.values(payload.errors)[0][0] : 'Tidak dapat menghitung pratinjau.';
                            throw new Error(firstMessage);
                        }

                        this.preview = payload;
                    } catch (fetchError) {
                        this.preview = null;
                        this.error = fetchError.message;
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
@endpush