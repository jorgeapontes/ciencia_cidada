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
            .container { 
                background-color: white;
                padding: 30px; 
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                text-align: center; 
            }
            h1 { 
                color: #d9534f; 
                margin-bottom: 20px;
            }
            p { 
                margin-bottom: 15px; 
            }
            .login-link { 
                color: #007bff; text-decoration: none; font-weight: bold;
             }
            .login-link:hover { 
                text-decoration: underline;
             }
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
    $atropelamento = $_POST["atropelamento"]; 
    
    $categoria_principal = $_POST["categoria"] ?? null;
    $sub_categoria_final = null;
    $nome_cientifico = $_POST['nome_cientifico'] ?? null; // Receber o nome científico

    if ($categoria_principal === 'animal') {
        $sub_categoria_final = $_POST['sub_categoria_animal'] ?? null;
    } elseif ($categoria_principal === 'planta') {
        $sub_categoria_final = $_POST['sub_categoria_planta'] ?? null;
    }

    $caminho_foto = '';
    if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] == 0) {
        $pasta_destino = "fotos/";
        if (!is_dir($pasta_destino)) {
            mkdir($pasta_destino, 0777, true);
        }
        $nome_arquivo = uniqid() . "_" . basename($_FILES["foto"]["name"]);
        $caminho_foto = $pasta_destino . $nome_arquivo;
        move_uploaded_file($_FILES["foto"]["tmp_name"], $caminho_foto);
    }

    if ($atropelamento == '1') {
        $localizacao = $_POST['localizacao'] ?? '';
        $especie_atropelamento = $_POST['especie_atropelamento'] ?? ''; // Renomeado para evitar conflito com coluna da tabela
        $data_ocorrencia = $_POST['data_ocorrencia'] ?? null;

        // Inserir na tabela de atropelamentos
        // Adicionada a coluna nome_cientifico
        $stmt = $conn->prepare("INSERT INTO atropelamentos (usuario_id, data_ocorrencia, localizacao, especie, descricao, caminho_foto, data_postagem, categoria, sub_categoria, nome_cientifico) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)");
        // Atualizado bind_param para "issssssss"
        $stmt->bind_param("issssssss", $_SESSION['usuario_id'], $data_ocorrencia, $localizacao, $especie_atropelamento, $descricao, $caminho_foto, $categoria_principal, $sub_categoria_final, $nome_cientifico);
        $tabela_destino = 'feed_atropelamentos.php';
    } else {
        $caminho_foto_value = $caminho_foto ?? '';

        // Inserir na tabela de publicações (feed geral)
        // Adicionada a coluna nome_cientifico
        $stmt = $conn->prepare("INSERT INTO publicacoes (usuario_id, titulo, descricao, caminho_foto, data_publicacao, atropelamento, categoria, sub_categoria, nome_cientifico) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?)");
        // Atualizado bind_param para "isssisss"
        $stmt->bind_param("isssisss", $_SESSION['usuario_id'], $titulo, $descricao, $caminho_foto_value, $atropelamento, $categoria_principal, $sub_categoria_final, $nome_cientifico);
        $tabela_destino = 'feed_user.php';
    }

    if ($stmt->execute()) {
        header("Location: " . $tabela_destino);
        exit;
    } else {
        // Para depuração, exibir mais detalhes do erro:
        error_log("Erro ao publicar: " . $stmt->error . " SQL: " . $stmt->sqlstate); // Logar o erro
        echo "Erro ao publicar. Por favor, tente novamente. Detalhes do erro foram registrados."; // Mensagem genérica para o usuário
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
    <link rel="stylesheet" href="css/styles.css"> </head>
<body>
    <nav id="japi-navbar" class="navbar navbar-expand-lg navbar-dark bg-dark"> <div class="container-fluid">
            <a class="navbar-brand" href="#">JapiWiki</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                <div class="navbar-nav">
                    <a class="nav-link" href="home.php">Home</a>
                    <a class="nav-link" href="<?= htmlspecialchars($painel_voltar) ?>">Painel</a>
                    <a class="nav-link" href="feed_user.php">Feed</a>
                    <a class="nav-link" href="feed_atropelamentos.php">Atropelamentos</a>
                    <a class="nav-link active" aria-current="page" href="publicar.php">Publicar</a>
                    <a class="nav-link" href="logout.php">Sair</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2>Nova Publicação</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="titulo" class="form-label">Título (Nome Popular)</label>
                <input type="text" class="form-control" id="titulo" name="titulo" required>
            </div>
            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="5" required></textarea>
            </div>
            <div class="mb-3">
                <label for="foto" class="form-label">Foto</label>
                <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
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
                    <label for="especie_atropelamento" class="form-label">Espécie (do atropelamento - opcional)</label>
                    <input type="text" class="form-control" id="especie_atropelamento" name="especie_atropelamento">
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

            <div class="mb-3" id="campo-nome-cientifico" style="display: none;">
                <label for="nome_cientifico" class="form-label">Nome Científico (opcional)</label>
                <input type="text" class="form-control" id="nome_cientifico" name="nome_cientifico" placeholder="Ex: Homo sapiens">
            </div>

            <button type="submit" class="btn btn-primary">Publicar</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar/ocultar campos extras quando selecionar atropelamento
            const radiosAtropelamento = document.querySelectorAll('input[name="atropelamento"]');
            const camposAtropelamento = document.getElementById('campos-atropelamento');
            const dataOcorrenciaInput = document.getElementById('data_ocorrencia');

            radiosAtropelamento.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === '1') {
                        camposAtropelamento.style.display = 'block';
                        dataOcorrenciaInput.required = true;
                    } else {
                        camposAtropelamento.style.display = 'none';
                        dataOcorrenciaInput.required = false;
                    }
                });
            });
            // Estado inicial dos campos de atropelamento
            if (document.getElementById('atropelamento_sim').checked) {
                camposAtropelamento.style.display = 'block';
                dataOcorrenciaInput.required = true;
            } else {
                camposAtropelamento.style.display = 'none';
                dataOcorrenciaInput.required = false;
            }


            // Mostrar/ocultar sub-categorias, nome científico e definir 'required' dinamicamente
            const categoriaAnimalRadio = document.getElementById('categoria_animal');
            const categoriaPlantaRadio = document.getElementById('categoria_planta');
            const subCategoriaAnimalDiv = document.getElementById('sub-categoria-animal-div');
            const subCategoriaPlantaDiv = document.getElementById('sub-categoria-planta-div');
            const campoNomeCientificoDiv = document.getElementById('campo-nome-cientifico'); // Adicionado
            const subCategoriaAnimalRadios = document.querySelectorAll('input[name="sub_categoria_animal"]');
            const subCategoriaPlantaRadios = document.querySelectorAll('input[name="sub_categoria_planta"]');

            function updateSubCategoriaVisibility() {
                let isAnimalChecked = categoriaAnimalRadio.checked;
                let isPlantaChecked = categoriaPlantaRadio.checked;

                subCategoriaAnimalDiv.style.display = isAnimalChecked ? 'block' : 'none';
                subCategoriaPlantaDiv.style.display = isPlantaChecked ? 'block' : 'none';
                campoNomeCientificoDiv.style.display = (isAnimalChecked || isPlantaChecked) ? 'block' : 'none'; // Mostrar se animal ou planta

                subCategoriaAnimalRadios.forEach(r => { 
                    r.required = isAnimalChecked; 
                    if (!isAnimalChecked) r.checked = false;
                });
                subCategoriaPlantaRadios.forEach(r => { 
                    r.required = isPlantaChecked;
                    if (!isPlantaChecked) r.checked = false;
                });

                // Lógica para garantir que pelo menos uma subcategoria seja selecionada se a categoria principal estiver
                // (Opcional, mas recomendado para UX)
                if (isAnimalChecked && !Array.from(subCategoriaAnimalRadios).some(r => r.checked)) {
                    // Poderia marcar um default ou apenas confiar na validação HTML5
                    // subCategoriaAnimalRadios[0].checked = true; // Exemplo: marcar o primeiro como default
                }
                if (isPlantaChecked && !Array.from(subCategoriaPlantaRadios).some(r => r.checked)) {
                    // subCategoriaPlantaRadios[0].checked = true; // Exemplo
                }
            }

            categoriaAnimalRadio.addEventListener('change', updateSubCategoriaVisibility);
            categoriaPlantaRadio.addEventListener('change', updateSubCategoriaVisibility);

            // Chama a função no carregamento da página
            updateSubCategoriaVisibility();
        });
    </script>
</body>
</html>