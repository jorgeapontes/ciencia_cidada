<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    $html = <<<HTML
    <!DOCTYPE html>
    <html lang='pt-br'>
    <head>
        <meta charset='UTF-8'>
        <title>Acesso Negado</title>
        <style>
            body { font-family: sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
            .container { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); text-align: center; }
            h1 { color: #d9534f; margin-bottom: 20px; }
            p { margin-bottom: 15px; }
            .login-link { color: #007bff; text-decoration: none; font-weight: bold; }
            .login-link:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>Acesso Negado</h1>
            <p>Você precisa estar logado para acessar esta página.</p>
            <p><a href='login.php' class='login-link'>Fazer Login</a></p>
        </div>
    </body>
    </html>
    HTML;

    echo $html;
    exit;
}

include 'conexao.php';

// Verificar o cargo do usuário para determinar para qual painel redirecionar
$painel_voltar = 'painel_usuario.php'; // Padrão para usuários normais
if (isset($_SESSION['cargo'])) {
    if ($_SESSION['cargo'] === 'admin') {
        $painel_voltar = 'admin.php';
    } elseif ($_SESSION['cargo'] === 'especialista') {
        $painel_voltar = 'painel_especialista.php';
    }
}

// Processamento do formulário de publicação
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = $_POST["titulo"];
    $descricao = $_POST["descricao"];
    $atropelamento = $_POST["atropelamento"]; // Recebe '1' ou '0'
    
    $categoria_principal = $_POST["categoria"] ?? null; // "animal" ou "planta"
    $sub_categoria_final = null;

    if ($categoria_principal === 'animal') {
        $sub_categoria_final = $_POST['sub_categoria_animal'] ?? null;
    } elseif ($categoria_principal === 'planta') {
        $sub_categoria_final = $_POST['sub_categoria_planta'] ?? null;
    }

    // Processamento do upload da foto
    $caminho_foto = '';
    if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] == 0) {
        $pasta_destino = "fotos/";
        // Cria a pasta se não existir
        if (!is_dir($pasta_destino)) {
            mkdir($pasta_destino, 0777, true);
        }
        $nome_arquivo = uniqid() . "_" . basename($_FILES["foto"]["name"]);
        $caminho_foto = $pasta_destino . $nome_arquivo;
        move_uploaded_file($_FILES["foto"]["tmp_name"], $caminho_foto);
    }

    if ($atropelamento == '1') {
        // Preparar variáveis para atropelamento
        $localizacao = $_POST['localizacao'] ?? '';
        $especie = $_POST['especie'] ?? ''; // Este campo 'especie' é o do formulário de atropelamento
        $data_ocorrencia = $_POST['data_ocorrencia'] ?? null;

        // Inserir na tabela de atropelamentos
        // MODIFICADO: 'categoria' recebe $categoria_principal, e adicionada 'sub_categoria' para $sub_categoria_final
        $stmt = $conn->prepare("INSERT INTO atropelamentos (usuario_id, data_ocorrencia, localizacao, especie, descricao, caminho_foto, data_postagem, categoria, sub_categoria) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
        // MODIFICADO: bind_param atualizado para "isssssss" e variáveis correspondentes
        $stmt->bind_param("isssssss", $_SESSION['usuario_id'], $data_ocorrencia, $localizacao, $especie, $descricao, $caminho_foto, $categoria_principal, $sub_categoria_final);
        $tabela_destino = 'feed_atropelamentos.php';
    } else {
        // Preparar variáveis para publicação normal
        $caminho_foto_value = $caminho_foto ?? '';

        // Inserir na tabela de publicações (feed geral)
        // MODIFICADO: 'categoria' recebe $categoria_principal, e adicionada 'sub_categoria' para $sub_categoria_final
        $stmt = $conn->prepare("INSERT INTO publicacoes (usuario_id, titulo, descricao, caminho_foto, data_publicacao, atropelamento, categoria, sub_categoria) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)");
        // MODIFICADO: bind_param atualizado para "isssiss" e variáveis correspondentes
        $stmt->bind_param("isssiss", $_SESSION['usuario_id'], $titulo, $descricao, $caminho_foto_value, $atropelamento, $categoria_principal, $sub_categoria_final);
        $tabela_destino = 'feed_user.php';
    }

    if ($stmt->execute()) {
        header("Location: " . $tabela_destino);
        exit;
    } else {
        echo "Erro ao publicar: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <nav id="japi-navbar" class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">JapiWiki</a>
            <div class="navbar-nav">
                <a class="nav-link" href="home.php">Home</a>
                <a class="nav-link" href="<?= htmlspecialchars($painel_voltar) ?>">Painel</a>
                <a class="nav-link" href="feed_user.php">Feed</a>
                <a class="nav-link" href="feed_atropelamentos.php">Atropelamentos</a>
                <a class="nav-link active" href="publicar.php">Publicar</a>
                <a class="nav-link" href="logout.php">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2>Nova Publicação</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="titulo" class="form-label">Título</label>
                <input type="text" class="form-control" id="titulo" name="titulo" required>
            </div>
            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="5" required></textarea>
            </div>
            <div class="mb-3">
                <label for="foto" class="form-label">Foto</label>
                <input type="file" class="form-control" id="foto" name="foto">
            </div>
            <div class="mb-3">
                <label class="form-label">Este é um caso de atropelamento?</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="atropelamento" id="atropelamento_sim" value="1" required>
                    <label class="form-check-label" for="atropelamento_sim">Sim</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="atropelamento" id="atropelamento_nao" value="0" required checked>
                    <label class="form-check-label" for="atropelamento_nao">Não</label>
                </div>
            </div>
            <div id="campos-atropelamento" style="display: none;">
                <div class="mb-3">
                    <label for="localizacao" class="form-label">Localização (opcional)</label>
                    <input type="text" class="form-control" id="localizacao" name="localizacao">
                </div>
                <div class="mb-3">
                    <label for="especie" class="form-label">Espécie (do atropelamento - opcional)</label>
                    <input type="text" class="form-control" id="especie" name="especie">
                </div>
                <div class="mb-3">
                    <label for="data_ocorrencia" class="form-label">Data e Hora da Ocorrência</label>
                    <input type="datetime-local" class="form-control" id="data_ocorrencia" name="data_ocorrencia">
                </div>
            </div>

            <div class="mb-3" id="campos-categoria">
                <label class="form-label">Categoria da Publicação</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="categoria" id="categoria_animal" value="animal" required>
                    <label class="form-check-label" for="categoria_animal">Animal</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="categoria" id="categoria_planta" value="planta" required>
                    <label class="form-check-label" for="categoria_planta">Planta</label>
                </div>
            </div>

            <div class="mb-3" id="sub-categoria-animal-div" style="display: none;">
                <label class="form-label">Especifique o Tipo de Animal:</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="sub_categoria_animal" id="animal_anfibio" value="Anfibio">
                    <label class="form-check-label" for="animal_anfibio">Anfíbio</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="sub_categoria_animal" id="animal_mamifero" value="Mamifero">
                    <label class="form-check-label" for="animal_mamifero">Mamífero</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="sub_categoria_animal" id="animal_ave" value="Ave">
                    <label class="form-check-label" for="animal_ave">Ave</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="sub_categoria_animal" id="animal_reptil" value="Reptil">
                    <label class="form-check-label" for="animal_reptil">Réptil</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="sub_categoria_animal" id="animal_peixe" value="Peixe">
                    <label class="form-check-label" for="animal_peixe">Peixe</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="sub_categoria_animal" id="animal_inseto" value="Inseto">
                    <label class="form-check-label" for="animal_inseto">Inseto</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="sub_categoria_animal" id="animal_outros" value="Outro Animal">
                    <label class="form-check-label" for="animal_outros">Outros</label>
                </div>
            </div>

            <div class="mb-3" id="sub-categoria-planta-div" style="display: none;">
                <label class="form-label">Especifique o Tipo de Planta:</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="sub_categoria_planta" id="planta_arvore" value="Arvore">
                    <label class="form-check-label" for="planta_arvore">Árvore</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="sub_categoria_planta" id="planta_arbusto" value="Arbusto">
                    <label class="form-check-label" for="planta_arbusto">Arbusto</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="sub_categoria_planta" id="planta_rasteira" value="Rasteira">
                    <label class="form-check-label" for="planta_rasteira">Rasteira</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Publicar</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar/ocultar campos extras quando selecionar atropelamento
        document.querySelectorAll('input[name="atropelamento"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const camposAtropelamento = document.getElementById('campos-atropelamento');
                if (this.value === '1') {
                    camposAtropelamento.style.display = 'block';
                     // Definir campos de atropelamento como obrigatórios se "Sim"
                    document.getElementById('data_ocorrencia').required = true;
                    // localizacao e especie são opcionais, não precisam de 'required' aqui
                } else {
                    camposAtropelamento.style.display = 'none';
                    // Remover obrigatoriedade se "Não"
                    document.getElementById('data_ocorrencia').required = false;
                }
            });
        });

        // Mostrar/ocultar sub-categorias e definir 'required' dinamicamente
        const categoriaAnimalRadio = document.getElementById('categoria_animal');
        const categoriaPlantaRadio = document.getElementById('categoria_planta');
        const subCategoriaAnimalDiv = document.getElementById('sub-categoria-animal-div');
        const subCategoriaPlantaDiv = document.getElementById('sub-categoria-planta-div');
        const subCategoriaAnimalRadios = document.querySelectorAll('input[name="sub_categoria_animal"]');
        const subCategoriaPlantaRadios = document.querySelectorAll('input[name="sub_categoria_planta"]');

        function updateSubCategoriaVisibility() {
            if (categoriaAnimalRadio.checked) {
                subCategoriaAnimalDiv.style.display = 'block';
                subCategoriaPlantaDiv.style.display = 'none';
                let isAnimalSubCategoryChecked = false;
                subCategoriaAnimalRadios.forEach(r => {
                    r.required = true;
                    if(r.checked) isAnimalSubCategoryChecked = true;
                });
                 // Se nenhuma subcategoria de animal estiver marcada, marque a primeira como default e required
                if(!isAnimalSubCategoryChecked && subCategoriaAnimalRadios.length > 0){
                   // subCategoriaAnimalRadios[0].checked = true; // Opcional: marcar uma default
                }

                subCategoriaPlantaRadios.forEach(r => { 
                    r.required = false; 
                    r.checked = false; 
                });
            } else if (categoriaPlantaRadio.checked) {
                subCategoriaAnimalDiv.style.display = 'none';
                subCategoriaPlantaDiv.style.display = 'block';
                subCategoriaAnimalRadios.forEach(r => { 
                    r.required = false; 
                    r.checked = false; 
                });

                let isPlantaSubCategoryChecked = false;
                subCategoriaPlantaRadios.forEach(r => {
                    r.required = true;
                    if(r.checked) isPlantaSubCategoryChecked = true;
                });
                // Se nenhuma subcategoria de planta estiver marcada, marque a primeira como default e required
                if(!isPlantaSubCategoryChecked && subCategoriaPlantaRadios.length > 0){
                   // subCategoriaPlantaRadios[0].checked = true; // Opcional: marcar uma default
                }

            } else { // Caso nenhuma categoria principal esteja selecionada (inicialmente)
                subCategoriaAnimalDiv.style.display = 'none';
                subCategoriaPlantaDiv.style.display = 'none';
                subCategoriaAnimalRadios.forEach(r => { r.required = false; r.checked = false; });
                subCategoriaPlantaRadios.forEach(r => { r.required = false; r.checked = false; });
            }
        }

        categoriaAnimalRadio.addEventListener('change', updateSubCategoriaVisibility);
        categoriaPlantaRadio.addEventListener('change', updateSubCategoriaVisibility);

        // Chama a função no carregamento da página para garantir o estado correto caso o formulário
        // seja recarregado com valores (embora neste caso não haja pré-seleção default para categorias)
        updateSubCategoriaVisibility();

        // Ajuste para o campo data_ocorrencia: torná-lo obrigatório apenas se atropelamento for 'Sim'
        // E garantir que não seja obrigatório se atropelamento for 'Não' no carregamento inicial
        if (document.getElementById('atropelamento_nao').checked) {
             document.getElementById('data_ocorrencia').required = false;
        }

    </script>
</body>
</html>