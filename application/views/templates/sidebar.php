<?php
/**
 * @var $data
 */
?>
<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="<?= $data->site->homePage ?>" class="brand-link">
        <img src="/img/AdminLTELogo.png" alt="<?= $data->site->name ?>" class="brand-image img-circle elevation-3"
             style="opacity: .8">
        <span class="brand-text font-weight-light"><?= $data->site->name ?></span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="<?= $data->user->avatar ?>" class="img-circle elevation-2" alt="<?= $data->user->name ?>">
            </div>
            <div class="info">
                <a href="#" class="d-block"><?= $data->user->name ?></a>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Add icons to the links using the .nav-icon class
                     with font-awesome or any other icon font library -->
                <li class="nav-item <?php if($this->router->class=='statistics') echo 'menu-open';?>">
                    <a href="#" class="nav-link <?php if($this->router->class=='statistics') echo 'active';?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>
                            Заявки
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="/statistics/" class="nav-link <?php if($this->router->class=='statistics' && $this->router->method=='index') echo 'active';?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Статистика</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="disabled nav-link <?php if($this->router->class=='statistics' && $this->router->method=='filelist') echo 'active';?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Файлы</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php
                if ($this->ion_auth->is_admin()) {
                    ?>
                    <li class="nav-item <?php if($this->router->class=='lidogenerators') echo 'menu-open';?>">
                        <a href="#" class="nav-link <?php if($this->router->class=='lidogenerators') echo 'active';?>">
                            <i class="nav-icon fas fa-business-time"></i>
                            <p>
                                Лидогенераторы
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="/lidogenerators/" class="nav-link <?php if($this->router->class=='lidogenerators' && $this->router->class=='index') echo 'active';?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Статистика</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/lidogenerators/settings" class="nav-link <?php if($this->router->class=='lidogenerators' && $this->router->class=='settings') echo 'active';?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Настройка</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/lidogenerators/list" class="nav-link <?php if($this->router->class=='lidogenerators' && $this->router->class=='list') echo 'active';?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Список</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>
                                Пользователи/Группы
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="/auth/users" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Пользователи</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/auth/groups" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Группы</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/auth/rules" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Разрешения</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php
                }
                ?>
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>

