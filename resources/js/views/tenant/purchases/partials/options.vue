<template>
    <el-dialog :title="titleDialog" :visible="showDialog" @open="create" width="30%"
               :close-on-click-modal="false"
               :close-on-press-escape="false"
               :show-close="false">
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 text-center font-weight-bold mt-5">
                <button type="button" class="btn btn-lg btn-info waves-effect waves-light" @click="clickDownload()">
                    <i class="fa fa-file-pdf"></i>
                </button>
                <p>Descargar PDF</p>
            </div>
        </div>
        <span slot="footer" class="dialog-footer">
            <template v-if="showClose">
                <el-button @click="clickClose">Cerrar</el-button>
            </template>
            <template v-else>
                <el-button @click="clickFinalize">Ir al listado</el-button>
                <el-button type="primary" @click="clickNewDocument">Nueva compra</el-button>
            </template>
        </span>
    </el-dialog>
</template>

<script>

    export default {
        props: ['showDialog', 'recordId', 'showClose', 'type'],
        data() {
            return {
                titleDialog: null,
                loading: false,
                resource: 'purchases',
                errors: {},
                form: {},
            }
        },
        created() {
            this.initForm()
        },
        methods: {
            initForm() {
                this.errors = {}
                this.form = {
                    id: null,
                    external_id: null,
                    number: null,
                    customer_email: null,
                    download_pdf: null
                }
            },
            create() {
                this.$http.get(`/${this.resource}/record/${this.recordId}`)
                    .then(response => {
                        this.form = response.data.data
                        let typei = this.type == 'edit' ? 'editada' : 'registrada'
                        this.titleDialog = `Compra ${typei}: ` +this.form.number
                        if(this.type === 'note') {
                            this.titleDialog = `Compra: ` +this.form.number
                        }
                    })
            },

            clickFinalize() {
                location.href = `/${this.resource}`
            },
            clickNewDocument() {
                if(this.type === 'note') {
                    location.href = `/${this.resource}/create`
                }
                this.clickClose()
            },
            clickClose() {
                this.$emit('update:showDialog', false)
                this.initForm()
            },
            clickDownload() {
                window.open( `/${this.resource}/pdf/${this.recordId}`, '_blank');
            },
        }
    }
</script>