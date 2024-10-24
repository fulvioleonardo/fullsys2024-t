<template>
    <div>
        <el-dialog @open="create"
            :title="record.number_full"
            :visible="showDialog"
            :show-close="false">
            <div class="row">
                <div class="col-lg-12">
                    <div class="form-group">
                        <label class="control-label">Seleccione resolucion electronica a utilizar</label>
                        <el-select v-model="document.type_document_id" class="border-left rounded-left border-info">
                            <el-option v-for="option in type_documents" :key="option.id" :value="option.id" :label="`${option.prefix} / ${option.resolution_number} / ${option.from} / ${option.to}`"></el-option>
                        </el-select>
                    </div>
                </div>
            </div>

            <span slot="footer" class="dialog-footer">
                <el-button @click="clickClose">Cerrar</el-button>
                <el-button
                    class="submit"
                    type="primary"
                    @click="submit"
                    :loading="loading_submit"
                    v-if="!generate_success">
                    Generar
                </el-button>
                <el-button @click="clickFinalize" v-else>Ir al listado</el-button>
            </span>
        </el-dialog>
        <document-options
            :showDialog.sync="showDialogDocumentOptions"
            :recordId="documentNewId"
            :showDownload="true"
            :isContingency="false"
            :showClose="true"></document-options>
    </div>
</template>

<script>
import DocumentOptions from '@viewsModuleProColombia/tenant/document/partials/options.vue'
export default {
    props: ['showDialog', 'record'],
    components: { DocumentOptions },
    data() {
        return {
            resource: 'co-remissions',
            document: {},
            type_documents: [],
            form: {},
            loading_submit: false,
            generate_success: false,
            tax_amount_calculate: [],
            showDialogDocumentOptions: false,
            documentNewId: null,
        }
    },
    methods: {
        async create() {
            await this.$http.get(`/quotations/option/tables`).then(response => {
                this.type_documents = response.data.type_documents.filter(item => item.code == 1 && item.name != 'Factura Electronica de venta');
            });
        },
        clickClose() {
            this.$emit("update:showDialog", false);
        },
        async submit() {
            this.loading_submit = true;
            await this.assignDocument();
            await this.storeDocument();
            this.generate_success = true;
            this.loading_submit = false;
        },
        storeDocument(){
            this.$http
                .post(`/co-documents`, this.document)
                .then(response => {
                    if (response.data.success) {
                        this.documentNewId = response.data.data.id;
                        this.showDialogDocumentOptions = true;
                        this.$eventHub.$emit("reloadData");
                    } else {
                        if(response.data.errors){
                            const html = this.parseMesaageError(response.data.errors)
                            this.$message({
                                duration: 6000,
                                type: 'error',
                                dangerouslyUseHTMLString: true,
                                message: html
                            });
                        }
                    }
                })
                .catch(error => {
                    if (error.response.status === 422) {
                        console.error(error.response.data)
                    } else {
                        this.$message.error(error.response.data.message);
                    }
                })
                .finally(() => {
                    this.loading_submit = false;
                });
        },
        clickFinalize() {
            this.$eventHub.$emit('reloadData');
            this.$emit("update:showDialog", false);
            this.generate_success = false;
        },
        async assignDocument() {
            let q = this.record;
            this.document.remission_id = q.id,
            this.document.date_issue = q.date_of_issue
            this.document.customer_id = q.customer_id
            this.document.customer = q.customer
            this.document.currency_id = q.currency_id
            this.document.purchase_order = null
            this.document.total_discount = q.total_discount
            this.document.total_tax = q.total_tax
            this.document.subtotal = q.subtotal
            this.document.total = q.total
            this.document.sale = q.sale
            this.document.items = q.items
            this.document.taxes = q.taxes
            this.document.payments = q.payments;
            this.document.time_days_credit = 0,
            this.document.payment_form_id = 1,
            this.document.payment_method_id = 1,
            await this.document.items.forEach((it)=>{
                it.price = it.unit_price
            })
            this.document.service_invoice = await this.createInvoiceService();
            this.document.prefix = this.document.service_invoice.prefix;
            this.document.resolution_number = this.document.service_invoice.resolution_number
        },
        async createInvoiceService() {
            let resol = this.resolution_data(this.document.type_document_id)
            const invoice = {
                number: 0,
                type_document_id: 1,
                prefix: resol[0].prefix,
                resolution_number: resol[0].resolution_number
            };
            invoice.customer = await this.getCustomer();
            invoice.tax_totals = await this.getTaxTotal();
            invoice.legal_monetary_totals = await this.getLegacyMonetaryTotal();
            invoice.allowance_charges = await this.createAllowanceCharge(invoice.legal_monetary_totals.allowance_total_amount, invoice.legal_monetary_totals.line_extension_amount );
            invoice.invoice_lines = await this.getInvoiceLines();
            invoice.with_holding_tax_total = await this.getWithHolding();
            return invoice;
        },
        resolution_data(type_document_id){
            return this.type_documents.filter(item => item.id == type_document_id);
        },
        getCustomer() {
            let customer = this.document.customer
            let obj = {
                identification_number: customer.number,
                name: customer.name,
                phone: customer.telephone,
                address: customer.address,
                email: customer.email,
                merchant_registration: "000000"
            };
            if (customer.type_person_id == 1) { //persona juridica
                obj.dv = customer.dv;
            }
            return obj;
        },
        getTaxTotal() {
            let tax = [];
            this.document.items.forEach(element => {
                let find = tax.find(x => x.tax_id == element.tax.type_tax_id && x.percent == element.tax.rate);
                if(find)
                {
                    let indexobj = tax.findIndex(x => x.tax_id == element.tax.type_tax_id && x.percent == element.tax.rate);
                    tax.splice(indexobj, 1);
                    tax.push({
                        tax_id: find.tax_id,
                        tax_amount: this.cadenaDecimales(Number(find.tax_amount) + Number(element.total_tax)),
                        percent: this.cadenaDecimales(find.percent),
                        taxable_amount: this.cadenaDecimales(Number(find.taxable_amount) + Number(element.unit_price) * Number(element.quantity)) - Number(element.discount)
                    });
                }
                else {
                    tax.push({
                        tax_id: element.tax.type_tax_id,
                        tax_amount: this.cadenaDecimales(Number(element.total_tax)),
                        percent: this.cadenaDecimales(Number(element.tax.rate)),
                        taxable_amount: this.cadenaDecimales((Number(element.unit_price) * Number(element.quantity)) - Number(element.discount))
                    });
                }
            });
            this.tax_amount_calculate = tax;
            return tax;
        },
        getLegacyMonetaryTotal() {
            let line_ext_am = 0;
            let tax_incl_am = 0;
            let allowance_total_amount = 0;
            this.document.items.forEach(element => {
                line_ext_am += (Number(element.unit_price) * Number(element.quantity)) - Number(element.discount);
                allowance_total_amount += Number(element.discount);
            });
            let total_tax_amount = 0;
            this.tax_amount_calculate.forEach(element => {
                total_tax_amount += Number(element.tax_amount);
            });
            tax_incl_am = line_ext_am + total_tax_amount;
            return {
                line_extension_amount: this.cadenaDecimales(line_ext_am),
                tax_exclusive_amount: this.cadenaDecimales(line_ext_am),
                tax_inclusive_amount: this.cadenaDecimales(tax_incl_am),
                allowance_total_amount: this.cadenaDecimales(allowance_total_amount),
                charge_total_amount: "0.00",
                payable_amount: this.cadenaDecimales(tax_incl_am - allowance_total_amount)
            };
        },
        getInvoiceLines() {
            let data = this.document.items.map(x => {
                return {
                    unit_measure_id: x.item.unit_type.code, //codigo api dian de unidad
                    invoiced_quantity: x.quantity,
                    line_extension_amount: this.cadenaDecimales((Number(x.unit_price) * Number(x.quantity)) - x.discount),
                    free_of_charge_indicator: false,
                            allowance_charges: [
                        {
                                    charge_indicator: false,
                                    allowance_charge_reason: "DESCUENTO GENERAL",
                                    amount: this.cadenaDecimales(x.discount),
                                    base_amount: this.cadenaDecimales(Number(x.unit_price) * Number(x.quantity))
                                }
                    ],
                    tax_totals: [
                        {
                            tax_id: x.tax.type_tax_id,
                            tax_amount: this.cadenaDecimales(x.total_tax),
                            taxable_amount: this.cadenaDecimales((Number(x.unit_price) * Number(x.quantity)) - x.discount),
                            percent: this.cadenaDecimales(x.tax.rate)
                        }
                    ],
                    description: x.item.name,
                    code: x.item.internal_id,
                    type_item_identification_id: 4,
                    price_amount: this.cadenaDecimales(x.unit_price),
                    base_quantity: x.quantity
                };
            });
            return data;
        },
        getWithHolding() {
            let total = this.document.sale
            let list = this.document.taxes.filter(function(x) {
                return x.is_retention && x.apply;
            });
            return list.map(x => {
                return {
                    tax_id: x.type_tax_id,
                    tax_amount: this.cadenaDecimales(x.retention),
                    percent: this.cadenaDecimales(x.rate),
                    taxable_amount: this.cadenaDecimales(total),
                };
            });
        },
        createAllowanceCharge(amount, base) {
            return [
                {
                    discount_id: 1,
                    charge_indicator: false,
                    allowance_charge_reason: "DESCUENTO GENERAL",
                    amount: this.cadenaDecimales(amount),
                    base_amount: this.cadenaDecimales(base)
                }
            ]
        },
        cadenaDecimales(amount){
            if(amount.toString().indexOf(".") != -1)
                return amount.toString();
            else
                return amount.toString()+".00";
        },
    }
}
</script>