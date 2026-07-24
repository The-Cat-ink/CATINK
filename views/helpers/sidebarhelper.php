<?php
require_once(__DIR__ . "/urlhelper.php");
require_once(__DIR__ . "/img.php");

if (!function_exists('renderSidebarNewsWidget')) {
    /**
     * Renderiza el widget lateral unificado "Lo más nuevo" y "Lo más popular".
     *
     * @param mysqli_result|array $ultimas   Resultado SQL o array de noticias recientes.
     * @param mysqli_result|array $populares Resultado SQL o array de noticias populares.
     * @param array|null          $pubCuadro Anuncio lateral si aplica.
     * @param array|null          $secciones Estado de secciones para publicidad.
     */
    function renderSidebarNewsWidget($ultimas, $populares, $pubCuadro = null, $secciones = null) {
        ?>
        <div class="sidebar-wrapper">
          <div class="sidebar-widget-card">
            <?php if (!empty($secciones['publicidad']['estado']) && !empty($pubCuadro)): ?>
              <div class="sidebar-ad-box mb-4">
                <a href="<?= htmlspecialchars($pubCuadro['url']) ?>" class="promo-link" data-pub="<?= (int)$pubCuadro['id_pub'] ?>" target="_blank" rel="noopener noreferrer" data-turbo="false">
                  <img src="<?= htmlspecialchars(imageUrl($pubCuadro['imagen'])) ?>" class="promo-card-media" loading="lazy" alt="Publicidad">
                </a>
                <span class="partner-tag">ADS</span>
              </div>
            <?php endif; ?>

            <!-- LO MÁS NUEVO -->
            <div class="sidebar-section">
              <div class="sidebar-section-header">
                <div class="sidebar-section-title">
                  <span class="sidebar-icon-pill icon-new"><i class="bi bi-clock-history"></i></span>
                  <a href="<?= recientesUrl() ?>" class="sidebar-title-link">Lo más nuevo</a>
                </div>
                <a href="<?= recientesUrl() ?>" class="sidebar-view-all">
                  Ver todo <i class="bi bi-chevron-right"></i>
                </a>
              </div>

              <div class="sidebar-news-list">
                <?php 
                if (is_object($ultimas) && method_exists($ultimas, 'data_seek')) {
                    $ultimas->data_seek(0);
                }
                $count = 0;
                while ($ultimas && ($row = (is_array($ultimas) ? ($ultimas[$count++] ?? null) : $ultimas->fetch_assoc()))):
                  $url = newsUrlFromRow($row);
                  $cover = imageUrl($row['crop3'] ?? $row['crop2'] ?? $row['crop1'] ?? null);
                  $titulo = htmlspecialchars($row['titulo']);
                  $cat = !empty($row['categoria']) ? htmlspecialchars($row['categoria']) : 'Noticia';
                ?>
                  <a href="<?= $url ?>" class="sidebar-news-item" data-article-id="<?= $row['id'] ?? '' ?>">
                    <div class="sidebar-thumb-wrap">
                      <img src="<?= $cover ?>" alt="<?= $titulo ?>" class="sidebar-thumb-img" loading="lazy">
                    </div>
                    <div class="sidebar-news-info">
                      <span class="sidebar-news-cat"><?= $cat ?></span>
                      <h4 class="sidebar-news-title"><?= $titulo ?></h4>
                    </div>
                  </a>
                <?php endwhile; ?>
              </div>
            </div>

            <!-- SEPARADOR -->
            <div class="sidebar-divider"></div>

            <!-- LO MÁS POPULAR -->
            <div class="sidebar-section">
              <div class="sidebar-section-header">
                <div class="sidebar-section-title">
                  <span class="sidebar-icon-pill icon-popular"><i class="bi bi-fire"></i></span>
                  <a href="<?= popularUrl() ?>" class="sidebar-title-link">Lo más popular</a>
                </div>
                <a href="<?= popularUrl() ?>" class="sidebar-view-all">
                  Ver todo <i class="bi bi-chevron-right"></i>
                </a>
              </div>

              <div class="sidebar-news-list">
                <?php 
                if (is_object($populares) && method_exists($populares, 'data_seek')) {
                    $populares->data_seek(0);
                }
                $rank = 1;
                $countPop = 0;
                while ($populares && ($row = (is_array($populares) ? ($populares[$countPop++] ?? null) : $populares->fetch_assoc()))):
                  $url = newsUrlFromRow($row);
                  $cover = imageUrl($row['crop3'] ?? $row['crop2'] ?? $row['crop1'] ?? null);
                  $titulo = htmlspecialchars($row['titulo']);
                  $cat = !empty($row['categoria']) ? htmlspecialchars($row['categoria']) : 'Top';
                ?>
                  <a href="<?= $url ?>" class="sidebar-news-item" data-article-id="<?= $row['id'] ?? '' ?>">
                    <div class="sidebar-thumb-wrap">
                      <img src="<?= $cover ?>" alt="<?= $titulo ?>" class="sidebar-thumb-img" loading="lazy">
                      <span class="sidebar-rank-badge rank-<?= $rank ?>"><?= $rank ?></span>
                    </div>
                    <div class="sidebar-news-info">
                      <span class="sidebar-news-cat text-accent"><?= $cat ?></span>
                      <h4 class="sidebar-news-title"><?= $titulo ?></h4>
                    </div>
                  </a>
                <?php 
                  $rank++;
                endwhile; 
                ?>
              </div>
            </div>

          </div>
        </div>
        <?php
    }
}
