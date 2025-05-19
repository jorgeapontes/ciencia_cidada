<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Cargo</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (!isset($conn)) {
                include 'conexao.php';
            }
            $usuarios = $conn->query("SELECT * FROM usuarios");
            while ($user = $usuarios->fetch_assoc()):
            ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= $user['nome'] ?></td>
                    <td><?= $user['email'] ?></td>
                    <td><?= ucfirst($user['cargo']) ?></td>
                    <td>
                        <form method="POST" class="action-form">
                            <input type="hidden" name="usuario_id" value="<?= $user['id'] ?>">
                            <select name="cargo" class="form-select form-select-sm me-2" style="width: auto;">
                                <option value="user" <?= $user['cargo'] === 'user' ? 'selected' : '' ?>>Usuário</option>
                                <option value="especialista" <?= $user['cargo'] === 'especialista' ? 'selected' : '' ?>>Especialista</option>
                                <option value="admin" <?= $user['cargo'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                            <button type="submit" name="alterar_cargo" class="btn btn-primary btn-sm">Alterar</button>
                            <input type="hidden" name="usuario_id_excluir" value="<?= $user['id'] ?>">
                            <button type="submit" name="excluir_usuario" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir este usuário e todos os seus dados relacionados?')">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>