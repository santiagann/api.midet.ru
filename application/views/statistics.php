<?php
/**
 * @var $data
 */
?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Статистика на текущий момент</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Статистика</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <!-- Info boxes -->
            <div class="row">
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-info elevation-1"><i class="fas fa-cog"></i></span>

                        <div class="info-box-content">
                            <span class="info-box-text">Всего заявок</span>
                            <span class="info-box-number"><?= $data->statReq->all ?></span>
                        </div>
                        <!-- /.info-box-content -->
                    </div>
                    <!-- /.info-box -->
                </div>
                <!-- /.col -->
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-success elevation-1"><i class="fas fa-hand-holding-usd"></i></span>

                        <div class="info-box-content">
                            <span class="info-box-text">Выданных</span>
                            <span class="info-box-number"><?= $data->statReq->issued ?></span>
                        </div>
                        <!-- /.info-box-content -->
                    </div>
                    <!-- /.info-box -->
                </div>
                <!-- /.col -->

                <!-- fix for small devices only -->
                <div class="clearfix hidden-md-up"></div>

                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-trash"></i></span>

                        <div class="info-box-content">
                            <span class="info-box-text">Отклоненных</span>
                            <span class="info-box-number"><?= $data->statReq->rejected ?></span>
                        </div>
                        <!-- /.info-box-content -->
                    </div>
                    <!-- /.info-box -->
                </div>
                <!-- /.col -->
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-thumbs-up"></i></span>

                        <div class="info-box-content">
                            <span class="info-box-text">Одобренных</span>
                            <span class="info-box-number"><?= $data->statReq->approved ?></span>
                        </div>
                        <!-- /.info-box-content -->
                    </div>
                    <!-- /.info-box -->
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->

            <!-- Main row -->
            <div class="row">
                <!-- Left col -->
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Загрузка отчёта</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col item">
                                    <div class="text-center">С</div>
                                    <div id="datafrom" style="display: flex; justify-content: center;"></div>
                                    <input type="hidden" id="datafrom_input" name="datafrom">
                                </div>
                                <div class="col item">
                                    <div class="text-center">До</div>
                                    <div id="datato" style="display: flex;  justify-content: center;"></div>
                                    <input type="hidden" id="datato_input" name="datato">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col item"></div>
                                <div class="col item" style="display: flex; justify-content: center">
                                    <button class="btn btn-primary" id="requestStat">
                                        Загрузить данные с <span id="txt_from"></span> по <span id="txt_to"></span>
                                    </button>
                                </div>
                                <a href="" style="display:none" id="getfile"></a>
                                <div class="col item"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div><!--/. container-fluid -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->
