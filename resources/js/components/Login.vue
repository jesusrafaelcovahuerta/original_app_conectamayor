<template>
     <div class="flex flex-wrap w-full justify-center items-center pt-56">
        <div class="flex flex-wrap max-w-xl">
            <div class="p-2 text-2xl text-gray-800 font-semibold"><h1>Ingresar a la cuenta</h1></div>
            <div class="p-2 w-full">
                <label for="email">RUT</label>
                <input class="w-full bg-gray-100 rounded border border-gray-400 focus:outline-none focus:border-indigo-500 text-base px-4 py-2" placeholder="RUT" type="email" v-model="form.rut">
            </div>
            <div class="p-2 w-full">
                <label for="password">Contraseña</label>
                <input class="w-full bg-gray-100 rounded border border-gray-400 focus:outline-none focus:border-indigo-500 text-base px-4 py-2" placeholder="Contraseña" type="password" v-model="form.password" name="password">
            </div>
            <div class="p-2 w-full mt-4">
                <button @click.prevent="loginUser" type="submit" class="flex text-white bg-indigo-500 border-0 py-2 px-8 focus:outline-none hover:bg-indigo-600 rounded text-lg">Ingresar</button>
            </div>
            <center><router-link :to="`/recover`">¿Olvidastes tu contraseña?</router-link></center>
        </div> 
    </div>
</template>
<script>
export default {
    data(){
        return{
            form:{
                rut: '',
                password: ''
            },
            errors: []
        }
    },
    methods:{
    }
}
</script>