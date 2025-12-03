<div class="sidebar p-3">
    <h4 class="text-center mb-4">Almoxarifado TI</h4>
    <hr class="bg-light">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= ($pagina_atual ?? '') == 'index.php' ? 'active' : '' ?>" 
               href="<?= $base_path ?>../index.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?= in_array($pagina_atual ?? '', ['fornecedores_cadastrados.php', 'fornecedores.php']) ? 'active' : '' ?>" 
               href="#" 
               id="fornecedoresDropdown" 
               role="button"
               data-bs-toggle="dropdown" 
               aria-expanded="false">
                <i class="bi bi-box-seam"></i> Fornecedores
            </a>
            <ul class="dropdown-menu" aria-labelledby="fornecedoresDropdown">
                <li>
                    <a class="dropdown-item <?= ($pagina_atual ?? '') == 'fornecedores_cadastrados.php' ? 'active' : '' ?>" 
                       href="fornecedores_cadastrados.php">
                        <i class="bi bi-truck"></i> Fornecedores Cadastrados
                    </a>
                </li>
                <li>
                    <a class="dropdown-item <?= ($pagina_atual ?? '') == 'fornecedores.php' ? 'active' : '' ?>" 
                       href="fornecedores.php">
                        <i class="bi bi-plus-square"></i> Cadastrar Fornecedor
                    </a>
                </li>
            </ul>
        </li>
        
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?= in_array($pagina_atual ?? '', ['computadores_cadastrados.php', 'computadores.php']) ? 'active' : '' ?>" 
               href="#" 
               id="computadoresDropdown" 
               role="button"
               data-bs-toggle="dropdown" 
               aria-expanded="false">
                <i class="bi bi-laptop"></i> Computadores
            </a>
            <ul class="dropdown-menu" aria-labelledby="computadoresDropdown">
                <li>
                    <a class="dropdown-item <?= ($pagina_atual ?? '') == 'computadores_cadastrados.php' ? 'active' : '' ?>" 
                       href="computadores_cadastrados.php">
                        <i class="bi bi-laptop"></i> Computadores Cadastrados
                    </a>
                </li>
                <li>
                    <a class="dropdown-item <?= ($pagina_atual ?? '') == 'computadores.php' ? 'active' : '' ?>" 
                       href="computadores.php">
                        <i class="bi bi-plus-square"></i> Cadastrar Computadores
                    </a>
                </li>
            </ul>
        </li>

        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?= in_array($pagina_atual ?? '', ['itens_cadastrados.php', 'itens.php']) ? 'active' : '' ?>" 
               href="#" 
               id="itensDropdown" 
               role="button"
               data-bs-toggle="dropdown" 
               aria-expanded="false">
                <i class="bi bi-box-seam"></i> Itens
            </a>
            <ul class="dropdown-menu" aria-labelledby="itensDropdown">
                <li>
                    <a class="dropdown-item <?= ($pagina_atual ?? '') == 'itens_cadastrados.php' ? 'active' : '' ?>" 
                       href="itens_cadastrados.php">
                        <i class="bi bi-archive"></i> Itens Cadastrados
                    </a>
                </li>
                <li>
                    <a class="dropdown-item <?= ($pagina_atual ?? '') == 'itens.php' ? 'active' : '' ?>" 
                       href="itens.php">
                        <i class="bi bi-plus-square"></i> Cadastrar Itens
                    </a>
                </li>
            </ul>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= ($pagina_atual ?? '') == 'estoque.php' ? 'active' : '' ?>" 
               href="estoque.php">
                <i class="bi bi-archive"></i> Estoque
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= ($pagina_atual ?? '') == 'entrada.php' ? 'active' : '' ?>" 
               href="entrada.php">
                <i class="bi bi-box-arrow-in-down"></i> Entradas
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= ($pagina_atual ?? '') == 'saida.php' ? 'active' : '' ?>" 
               href="saida.php">
                <i class="bi bi-box-arrow-up"></i> Saídas
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= ($pagina_atual ?? '') == 'configuracoes.php' ? 'active' : '' ?>" 
               href="configuracoes.php">
                <i class="bi bi-gear"></i> Configurações
            </a>
        </li>

        <li class="nav-item mt-4">
            <a class="nav-link text-danger" href="<?= $auth_path ?>logout.php">
                <i class="bi bi-box-arrow-right"></i> Sair
            </a>
        </li>
    </ul>
</div>