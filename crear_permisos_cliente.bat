@echo off
echo.
echo Registrando permisos de Cliente...
echo.

php -r "require 'vendor/autoload.php'; $app = require_once 'bootstrap/app.php'; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); use Illuminate\Support\Facades\DB; $permisos = ['view_cliente','view_any_cliente','create_cliente','update_cliente','delete_cliente','delete_any_cliente','force_delete_cliente','force_delete_any_cliente','restore_cliente','restore_any_cliente','replicate_cliente','reorder_cliente']; foreach($permisos as $p) { DB::table('permissions')->insertOrIgnore(['name'=>$p,'guard_name'=>'web','created_at'=>now(),'updated_at'=>now()]); } echo 'Permisos creados\n'; $roles = DB::table('roles')->get(); foreach($roles as $rol) { foreach($permisos as $p) { $permId = DB::table('permissions')->where('name',$p)->value('id'); if($permId) { DB::table('role_has_permissions')->insertOrIgnore(['permission_id'=>$permId,'role_id'=>$rol->id]); } } echo 'Rol: '.$rol->name.' actualizado\n'; } echo 'COMPLETADO\n';"

echo.
echo Limpiando cache...
php artisan optimize:clear >nul 2>&1

echo.
echo ===============================================
echo   COMPLETADO
echo ===============================================
echo.
echo Ahora:
echo   1. Presiona Ctrl + Shift + R en tu navegador
echo   2. Ve a Shield - Roles
echo   3. Edita tu rol
echo   4. Deberias ver los permisos de Cliente
echo.
pause
