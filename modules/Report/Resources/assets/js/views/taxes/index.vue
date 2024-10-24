<template>
  <div class="card mb-0 pt-2 pt-md-0">
    <div class="card-header bg-info">
      <h3 class="my-0">Reporte impuestos</h3>
    </div>
    <div class="card mb-0">
      <div class="card-body">
        <div class="row">
          <div class="col-md-12 col-lg-12 col-xl-12">
            <div class="row mt-2">
              <div class="col-md-3">
                <label class="control-label">Fecha desde</label>
                <el-date-picker
                  v-model="form.date_start"
                  type="date"
                  @change="changeDisabledDates"
                  value-format="yyyy-MM-dd"
                  format="dd/MM/yyyy"
                  :clearable="true"
                ></el-date-picker>
              </div>
              <div class="col-md-3">
                <label class="control-label">Fecha hasta</label>
                <el-date-picker
                  v-model="form.date_end"
                  type="date"
                  :picker-options="pickerOptionsDates"
                  value-format="yyyy-MM-dd"
                  format="dd/MM/yyyy"
                  :clearable="true"
                ></el-date-picker>
              </div>

              <div class="col-md-3" style="margin-top:29px">
                <el-button

                  class="submit"
                  type="primary"
                  @click.prevent="getRecordsByFilter"
                  :loading="loading_submit"
                  icon="el-icon-search"
                >Buscar</el-button>
                <template>
                  <el-button v-if="records.length > 0" class="submit" type="success" @click.prevent="clickDownload(activeTab)">
                    <i class="fa fa-file-excel"></i> Exportal Excel
                  </el-button>
                </template>
              </div>
            </div>
            <div class="row mt-1 mb-4"></div>
          </div>

          <div class="col-md-12">
            <el-tabs v-model="activeTab">
              <el-tab-pane label="Ventas" name="sales">
                <div class="table-responsive">
                  <table class="table">
                    <thead>
                      <tr>
                        <th class>#</th>
                        <th class="text-left">Fecha emisión</th>
                        <th class="text-center">Cliente</th>
                        <th class>Documento</th>
                        <th class="text-right">Base</th>
                        <th class="text-right">Descuento</th>
                        <th class="text-right" v-for="(col, index) in columnsTitles" :key="index + 'T'">
                            {{ col.text }}
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(row, index) in records" :key="index + 'R'">
                        <td>{{ index }}</td>
                        <td class="text-left">{{row.created_at}}</td>
                        <td class="text-center">{{row.customer.name}}</td>

                        <td class>
                          <div>{{row.type_document.name}}</div>
                          <div>
                            {{row.prefix}}{{row.number}}
                            <template
                              v-if="row.type_document_id && row.type_document_id != 1"
                            >({{row.prefix}}{{row.number}})</template>
                          </div>
                        </td>

                        <td class="text-right">$ {{row.total}}</td>
                        <td class="text-right">$ {{row.total_discount}}</td>

                        <td class="text-right" v-for="(tax, index) in taxTitles" :key="index + 'TD'" >{{ratePrefix()}}{{getTaxTotalBill(tax, row.taxes)}}</td>

                      </tr>
                    </tbody>
                    <tfoot>
                        <td class="text-center" colspan="4"><strong>Totales:</strong></td>
                        <td class="text-right"><strong>{{ratePrefix()}}{{getFormatDecimal(getSaleTotal()) }}</strong></td>
                        <td class="text-right"><strong>{{ratePrefix()}}{{getFormatDecimal(getTotalDiscount()) }}</strong></td>
                        <td class="text-right" v-for="(tax, index) in taxTitles" :key="index + 'F'"><strong>{{ratePrefix()}}{{getFormatDecimal(getTaxTotal(tax)) }}</strong></td>
                    </tfoot>
                  </table>
                </div>
              </el-tab-pane>
              <el-tab-pane label="Compras" name="purchases">
                <div class="table-responsive">
                  <table class="table">
                    <thead>
                      <tr>
                        <th class>#</th>
                        <th class="text-left">Fecha emisión</th>
                        <th class="text-center">Cliente</th>
                        <th class>Documento</th>
                        <th class="text-right">Base</th>
                        <th class="text-right">Descuento</th>
                        <th class="text-right" v-for="(col, index) in columnsTitles" :key="index + 'H'">
                            {{ col.text }}
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(row, index) in dataPurchases" :key="index + 'B'">
                        <td>{{ index }}</td>
                        <td class="text-left">{{row.created_at}}</td>
                        <td class="text-center">{{row.customer.name}}</td>

                        <td class>
                          <div>{{row.type_document.name}}</div>
                          <div>
                            {{row.prefix}}{{row.number}}
                            <template
                              v-if="row.type_document_id && row.type_document_id != 1"
                            >({{row.prefix}}{{row.number}})</template>
                          </div>
                        </td>

                        <td class="text-right">$ {{row.total}}</td>
                        <td class="text-right">$ {{row.total_discount}}</td>

                        <td class="text-right" v-for="(tax, index) in taxTitles" :key="index + 'TD'" >{{ratePrefix()}}{{getTaxTotalBill(tax, row.taxes)}}</td>

                      </tr>
                    </tbody>
                    <tfoot>
                        <td class="text-center" colspan="4"><strong>Totales:</strong></td>
                        <td class="text-right"><strong>{{ratePrefix()}}{{getFormatDecimal(getSaleTotal(1)) }}</strong></td>
                        <td class="text-right"><strong>{{ratePrefix()}}{{getFormatDecimal(getTotalDiscount(1)) }}</strong></td>
                        <td class="text-right" v-for="(tax, index) in taxTitles" :key="index + 'F'"><strong>{{ratePrefix()}}{{getFormatDecimal(getTaxTotal(tax, 1)) }}</strong></td>
                    </tfoot>
                  </table>
                </div>
              </el-tab-pane>
            </el-tabs>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
    import moment from 'moment'
    import queryString from 'query-string'
    import {functions} from '@mixins/functions'

    export default {
        mixins: [functions],
        data () {
            return {
                loading_submit:false,
                columns: [],
                records: [],
                headers: headers_token,
                document_types: [],
                pagination: {},
                search: {},
                totals: {},
                establishment: null,
                users: [],
                form: {},
                pickerOptionsDates: {
                    disabledDate: (time) => {
                        time = moment(time).format('YYYY-MM-DD')
                        return this.form.date_start > time
                    }
                },
                resource: 'reports/taxes',
                taxTitles: [],
                taxesAll: [],
                dataPurchases: [],
                taxesPurchases: [],
                activeTab: 'sales',
            }
        },
        filters: {
            //numberFormat: value => numeral(value).format('0,0.00')
        },
        computed:{

            columnsTitles() {
                let titles = [];

                this.taxTitles.forEach(tax => titles.push({
                    text: `${tax.name} (${tax.rate})`,
                    value: null
                }));

                return titles;
            }
        },
        created() {
            this.initForm()
            this.$eventHub.$on('reloadData', () => {
                this.getRecords()
            })
        },
        async mounted () {

            /*await this.$http.get(`/${this.resource}/filter`)
                .then(response => {
                    this.document_types = response.data.document_types;
                    this.users = response.data.users;
                });*/


            //await this.getRecords()

        },
        methods: {
            getTotalDiscount(is_purchase = 0) {
                try {
                    return _.reduce(this.documents, (sum, value) => {
                        return Number(sum) + Number(value.total_discount);
                    }, 0);
                }
                catch(e) {
                    return 0;
                }
            },
            getSaleTotal(is_purchase = 0) {
                let records = this.records
                if(is_purchase) {
                  records = this.dataPurchases
                }
                try {
                    return _.reduce(records, (sum, value) => {
                        return Number(sum) + Number(value.sale);
                    }, 0);
                }
                catch(e) {
                    return 0;
                }
            },
            getTaxTotal(tax, is_purchase = 0) {
                let records = this.taxesAll
                if(is_purchase) {
                  records = this.taxesPurchases
                }
                try {
                    return _.reduce(records.filter(row => row.id == tax.id), (sum, value) => {
                        return tax.is_retention ? (Number(sum) + Number(value.retention)) : (Number(sum) + Number(value.total));
                    }, 0);
                }
                catch (e) {
                    return 0;
                }
            },
            getTaxTotalBill(tax, taxes) {
                try {
                    let exist = taxes.find(row => row.id == tax.id);
                    return exist.is_retention ? exist.retention : exist.total;
                }
                catch(e) {
                    return 0;
                }
            },
            ratePrefix(tax = null) {
                return '$'
            },
            changeDisabledDates() {
                if (this.form.date_end < this.form.date_start) {
                    this.form.date_end = this.form.date_start
                }
            },
            clickDownload(type) {
                let query = queryString.stringify({
                    ...this.form
                });
                switch (type) {
                  case 'purchases':
                    window.open(`/${this.resource}/${type}/excel/?${query}`, '_blank');
                    break;
                  default:
                    window.open(`/${this.resource}/excel/?${query}`, '_blank');
                    break;
                }
            },
            initForm(){

                this.form = {
                    date_start:null,
                    date_end:null,
                }
            },
            async getRecordsByFilter(){

                if(!this.form.date_start || !this.form.date_end)
                {
                    return this.$message.warning("Debe seleccionar fechas")
                }


                this.loading_submit = await true
                await this.getRecords()
                this.loading_submit = await false

            },
            getRecords() {
                return this.$http.get(`/${this.resource}/records?${this.getQueryParameters()}`).then((response) => {
                    this.records = response.data.data
                    this.taxTitles = response.data.taxTitles
                    this.taxesAll = response.data.taxesAll
                    this.dataPurchases = response.data.dataPurchases
                    this.taxesPurchases = response.data.taxesPurchases
                }).catch((response) => {
                  console.log(response);
                }).finally(() => {
                  this.loading_submit = false
                });
            },
            getQueryParameters() {
                return queryString.stringify({
                    page: this.pagination.current_page,
                    limit: this.limit,
                    ...this.form
                })
            },

        }
    }
</script>
