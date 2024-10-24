<template>
    <div>
        <el-dialog :title="titleDialog"
            :visible="showDialog"
            @close="clickClose"
            @open="create">
            <form autocomplete="off" @submit.prevent="submit">
                <div class="row pb-4">
                    <div class="col-12 col-sm-6 mt-2">
                        <a href="/formats/transaccion_ingreso_items.xlsx" target="_new" class="text-muted">Click  para descargar el formato</a>
                    </div>
                    <div class="col-12 col-sm-6 mt-2">
                        <el-dropdown>
                            <span class="el-dropdown-link text-muted">
                                Códigos de traslado<i class="el-icon-arrow-down el-icon--right"></i>
                            </span>
                            <el-dropdown-menu slot="dropdown">
                                <el-dropdown-item v-for="(row, index) in inventory_transactions" :key="index">
                                    {{ row.id }} : {{ row.name }}
                                </el-dropdown-item>
                            </el-dropdown-menu>
                        </el-dropdown>
                    </div>
                    <div class="col-12 mt-4">
                        <div class="form-group" :class="{'has-danger': errors.warehouse_id}">
                            <label class="control-label">Almacén</label>
                            <el-select v-model="warehouse_id">
                                <el-option v-for="option in warehouses" :key="option.id" :value="option.id" :label="option.description"></el-option>
                            </el-select>
                            <small class="form-control-feedback" v-if="errors.warehouse_id" v-text="errors.warehouse_id[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-12 mt-2">
                        <div class="form-group" :class="{'has-danger': errors.file}">
                            <el-upload
                                ref="upload"
                                :headers="headers"
                                :data="{'warehouse_id': warehouse_id}"
                                action="/inventory/transaction/import/input"
                                :show-file-list="true"
                                :auto-upload="false"
                                :multiple="false"
                                :on-error="errorUpload"
                                :limit="1"
                                :on-success="successUpload"
                                accept=".xlsx, .xls">
                                <el-button slot="trigger" type="primary">Seleccione un archivo (xls, xlsx) con la informacion a cargar...</el-button>
                            </el-upload>
                        </div>
                        <small class="form-control-feedback" v-if="errors.file" v-text="errors.file[0]"></small>
                    </div>
                </div>
                <span slot="footer">
                    <el-button @click="clickClose">Cerrar</el-button>
                    <el-button type="primary" native-type="submit" :loading="loading_submit">Procesar</el-button>
                </span>
            </form>
        </el-dialog>

    </div>
</template>

<style>
.prevent-word-break {
    word-break: keep-all;
    display: inline-block;
}
</style>

<script>
export default {
    props: ['showDialog'],
    data() {
        return {
            titleDialog: 'Importar ingresos',
            loading: false,
            resource: 'inventory',
            loading_submit:false,
            warehouse_id: null,
            warehouses: {},
            inventory_transactions: {},
            errors: {},
            headers: headers_token,
        }
    },
    methods: {
        async create() {
            await this.$http.get(`/${this.resource}/tables/transaction/input`)
                .then(response => {
                    this.warehouses = response.data.warehouses
                    this.inventory_transactions = response.data.inventory_transactions
                })
        },
        clickClose() {
            this.$emit('update:showDialog', false)
        },
        errorUpload(err) {
            try {
                console.error(err);
                const errorMessage = typeof err === "string" ? err : err.message;
                const jsonStart = errorMessage.indexOf('{');

                if (jsonStart !== -1) {
                    const jsonString = errorMessage.substring(jsonStart);
                    const errorJson = JSON.parse(jsonString);
                    this.$message.error(`Error al subir el archivo: ${errorJson.message}`);
                } else {
                    this.$message.error("Error inesperado: " + errorMessage);
                }
            } catch (e) {
                this.$message.error("Error inesperado: " + e.message);
            }
        },
        successUpload(response, file, fileList) {
            if (response.success) {
                this.$message.success(response.message)
                this.$eventHub.$emit('reloadData')
                this.$refs.upload.clearFiles()
                this.clickClose()
            } else {
                this.$message({message:response.message, type: 'error'})
            }
        },
        async submit() {
            if (!this.warehouse_id) {
                return this.$message.error('Debe seleccionar un Establecimiento')
            }
            this.loading_submit = true
            await this.$refs.upload.submit()
            this.loading_submit = false
        },
    }
}
</script>
