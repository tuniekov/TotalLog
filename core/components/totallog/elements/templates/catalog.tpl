<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>[[*pagetitle]] - [[++site_name]]</title>
    <meta name="description" content="[[*description:default=`[[*pagetitle]] - каталог товаров`]]">
    
    <!-- CSS -->
    <link href="[[++assets_url]]components/gtsshopsitetemplate/web/css/main.css" rel="stylesheet">
    
    <!-- Vue.js App CSS -->
    <style>
        .catalog-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .catalog-header {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .catalog-title {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .catalog-description {
            font-size: 1.1rem;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .vue-app-container {
            min-height: 400px;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <nav class="main-navigation">
                [[pdoMenu? &parents=`0` &level=`2` &tpl=`@INLINE <li><a href="[[+link]]">[[+menutitle]]</a></li>`]]
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="catalog-container">
            <!-- Page Header -->
            <div class="catalog-header">
                <h1 class="catalog-title">[[*pagetitle]]</h1>
                [[*content:notempty=`<div class="catalog-description">[[*content]]</div>`]]
            </div>

            <!-- Vue.js Application Container -->
            <div id="gtsshopsitetemplate" class="vue-app-container">
                <div class="loading">
                    Загрузка каталога...
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <p>&copy; [[++site_name]] [[!+year:default=`[[!+date:strtotime:date=`%Y`]]`]]</p>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="[[++assets_url]]components/gtsshopsitetemplate/web/js/main.js"></script>
    
    <!-- Vue App Configuration -->
    <script>
        // Конфигурация для Vue приложения
        window.gtsshopsitetemplateConfigs = {
            module: 'gsProductTree2', // Модуль по умолчанию
            apiUrl: '[[++site_url]]api/',
            resourceId: [[*id]],
            parentId: [[*parent]],
            // Дополнительные настройки
            settings: {
                itemsPerPage: 20,
                showFilters: true,
                showSearch: true
            }
        };
    </script>
</body>
</html>
