import * as THREE from 'three';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';
import { RoomEnvironment } from 'three/examples/jsm/environments/RoomEnvironment.js';
import { mergeGeometries } from 'three/examples/jsm/utils/BufferGeometryUtils.js';

// Mapa directo con los botones de navegación web y nodos del GLB
export const VISTAS = {
    general: { pos: 'CAM_POS_00_General', target: 'CAM_TARGET_00_General', hint: 'Vista 3D · Arrastra para explorar' },
    estantes: { pos: 'CAM_POS_01_Estantes', target: 'CAM_TARGET_01_Estantes', hint: 'Estantes 3D · Haz clic en un producto' },
    frescos: { pos: 'CAM_POS_02_Frescos', target: 'CAM_TARGET_02_Frescos', hint: 'Frescos 3D · Elige una canasta' },
    mostrador: { pos: 'CAM_POS_03_Mostrador', target: 'CAM_TARGET_03_Mostrador', hint: 'Mostrador · Pan artesanal y caja' },
};

const INTERACTIVE_DICTIONARY = [
    { prefix: 'PROD_Arroz_Costeno', label: 'Arroz Extra Costeño', zone: 'Estantes', queries: ['arroz', 'costeno'] },
    { prefix: 'PROD_Fondo_Arroz', label: 'Arroz de Despensa', zone: 'Estantes', queries: ['arroz'] },
    { prefix: 'PROD_Aceite_Vegetal', label: 'Aceite Vegetal Primor', zone: 'Estantes', queries: ['aceite', 'vegetal', 'primor'] },
    { prefix: 'PROD_Aceite_Oliva', label: 'Aceite de Oliva Extra', zone: 'Estantes', queries: ['oliva', 'aceite'] },
    { prefix: 'PROD_Fondo_Aceite', label: 'Aceite de Cocina', zone: 'Estantes', queries: ['aceite'] },
    { prefix: 'PROD_Azucar_Cartavio', label: 'Azúcar Rubia Cartavio', zone: 'Estantes', queries: ['azucar', 'cartavio'] },
    { prefix: 'PROD_Fondo_Azucar', label: 'Azúcar de Despensa', zone: 'Estantes', queries: ['azucar'] },
    { prefix: 'PROD_Frasco_Granos', label: 'Quinua y Granos Andinos', zone: 'Estantes', queries: ['quinua', 'granos'] },
    { prefix: 'MOSTRADOR_ITEM_FondoFrasco', label: 'Frasco de Granos y Miel', zone: 'Mostrador', queries: ['miel', 'quinua'] },
    { prefix: 'PROD_Leche_Gloria_Caja', label: 'Leche Evaporada Gloria', zone: 'Estantes', queries: ['leche', 'gloria'] },
    { prefix: 'PROD_Leche_PuraVida', label: 'Leche Pura Vida', zone: 'Estantes', queries: ['leche', 'pura'] },
    { prefix: 'PROD_Leche_Condensada', label: 'Leche Condensada', zone: 'Estantes', queries: ['condensada', 'leche'] },
    { prefix: 'PROD_Fondo_Leche', label: 'Lácteos en Caja', zone: 'Estantes', queries: ['leche'] },
    { prefix: 'PROD_Fondo_PuraVida', label: 'Leche y Bebidas Lácteas', zone: 'Estantes', queries: ['leche'] },
    { prefix: 'PROD_Queso_Bonle', label: 'Queso Fresco Bonlé', zone: 'Estantes', queries: ['queso'] },
    { prefix: 'PROD_Mantequilla_Gloria', label: 'Mantequilla Gloria', zone: 'Estantes', queries: ['mantequilla', 'lacteos'] },
    { prefix: 'PROD_Mermelada_Gloria', label: 'Mermelada Gloria', zone: 'Estantes', queries: ['mermelada', 'desayunos'] },
    { prefix: 'PROD_RepisaDer_Mermelada', label: 'Mermeladas y Conservas', zone: 'Estantes', queries: ['mermelada', 'miel'] },
    { prefix: 'PROD_Jugo_Gloria_Pina', label: 'Jugo Gloria Piña', zone: 'Estantes', queries: ['jugo', 'bebida'] },
    { prefix: 'PROD_Fondo_Jugo', label: 'Jugos y Néctares', zone: 'Estantes', queries: ['jugo', 'bebida'] },
    { prefix: 'PROD_Bebida_Artesanal', label: 'Bebida Artesanal de Bodega', zone: 'Estantes', queries: ['bebida', 'agua', 'cafe'] },
    { prefix: 'PROD_BolsaKraft', label: 'Café Pasado Orgánico', zone: 'Estantes', queries: ['cafe', 'chanchamayo'] },
    { prefix: 'PROD_Fondo_Kraft', label: 'Bolsa de Café / Granos', zone: 'Estantes', queries: ['cafe', 'quinua'] },
    { prefix: 'MOSTRADOR_ITEM_FondoKraft', label: 'Café Tostado Chanchamayo', zone: 'Mostrador', queries: ['cafe', 'chanchamayo'] },
    { prefix: 'PROD_Atun_Florida', label: 'Atún Florida', zone: 'Estantes', queries: ['atun', 'florida'] },
    { prefix: 'PROD_Atun_Compass', label: 'Conserva Compass', zone: 'Estantes', queries: ['atun', 'compass'] },
    { prefix: 'MOSTRADOR_ITEM_FondoLata', label: 'Conserva Seleccionada', zone: 'Mostrador', queries: ['atun', 'leche'] },
    { prefix: 'PROD_Fideos_DonVittorio', label: 'Fideos Don Vittorio', zone: 'Estantes', queries: ['fideos', 'abarrotes'] },
    { prefix: 'PROD_Fideos_SanJorge', label: 'Fideos San Jorge', zone: 'Estantes', queries: ['fideos', 'galletas'] },
    { prefix: 'PROD_Caja_Sugafor', label: 'Galletas y Desayunos', zone: 'Estantes', queries: ['galletas', 'avena', 'miel'] },
    { prefix: 'PROD_Balde_Alpesa', label: 'Detergente y Limpieza', zone: 'Estantes', queries: ['detergente', 'marsella', 'limpieza'] },
    { prefix: 'PROD_Fondo_Alpesa', label: 'Artículos de Limpieza', zone: 'Estantes', queries: ['detergente', 'limpieza'] },
    { prefix: 'PROD_Duraznos_Compass', label: 'Duraznos en Almíbar', zone: 'Estantes', queries: ['duraznos', 'mermelada'] },
    { prefix: 'PROD_Arveja_ValleNorte', label: 'Arveja Valle Norte', zone: 'Estantes', queries: ['arveja', 'abarrotes'] },
    { prefix: 'PROD_Salsa_Pomarola', label: 'Salsa Pomarola', zone: 'Estantes', queries: ['pomarola', 'salsa'] },
    { prefix: 'PROD_Sillao_Kikko', label: 'Sillao Kikko', zone: 'Estantes', queries: ['sillao', 'kikko'] },
    { prefix: 'FRESCO_Canasta_Limones', label: 'Limones Frescos', zone: 'Frescos', queries: ['limon'] },
    { prefix: 'FRESCO_Canasta_MaizMorado', label: 'Maíz Morado Peruano', zone: 'Frescos', queries: ['maiz', 'morado'] },
    { prefix: 'FRESCO_Canasta_Mangos', label: 'Mangos Frescos del Norte', zone: 'Frescos', queries: ['mango'] },
    { prefix: 'FRESCO_Canasta_Manzanas', label: 'Manzanas Frescas', zone: 'Frescos', queries: ['manzana'] },
    { prefix: 'FRESCO_Canasta_Naranjas', label: 'Naranjas de Mesa', zone: 'Frescos', queries: ['naranja'] },
    { prefix: 'FRESCO_Canasta_Paltas', label: 'Paltas Seleccionadas', zone: 'Frescos', queries: ['palta'] },
    { prefix: 'FRESCO_Canasta_Papas', label: 'Papas Peruanas', zone: 'Frescos', queries: ['papa'] },
    { prefix: 'FRESCO_Canasta_Papayas', label: 'Papayas Frescas', zone: 'Frescos', queries: ['papaya'] },
    { prefix: 'MOSTRADOR_ITEM_PanArtesanal', label: 'Pan Ciabatta / Masa Madre', zone: 'Mostrador', queries: ['pan', 'ciabatta'] },
    { prefix: 'MOSTRADOR_ITEM_PanVitrina', label: 'Panadería del Día', zone: 'Mostrador', queries: ['pan', 'ciabatta'] },
    {
        prefix: 'MOSTRADOR_ITEM_POS',
        label: 'Caja · Yape, Plin y Tarjetas',
        zone: 'Mostrador',
        queries: [],
        infoNote: 'Pagos rápidos en mostrador con Yape, Plin, tarjetas Visa/Mastercard y efectivo sin recargo.',
    },
    {
        prefix: 'CAJERO_',
        label: 'Atención de Bodega',
        zone: 'Mostrador',
        queries: [],
        infoNote: 'Te atendemos todos los días para armar tu lista o separar tu pedido por WhatsApp.',
    },
];

const IGNORED_NODE_PREFIXES = [
    'PROD_Decor_',
    'MOSTRADOR_ITEM_Panio_Lino',
    'MOSTRADOR_ITEM_Tabla_',
    'MOSTRADOR_ITEM_CampanaVidrio',
];

function normalizeText(value) {
    return String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
}

function easeInOutCubic(t) {
    return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
}

function resolveInteractiveMeta(rawName, catalog) {
    if (!rawName || IGNORED_NODE_PREFIXES.some((prefix) => rawName.startsWith(prefix))) {
        return null;
    }

    const dictEntry = INTERACTIVE_DICTIONARY.find((item) => rawName.startsWith(item.prefix));
    if (!dictEntry) {
        if (!/^(PROD_|FRESCO_|MOSTRADOR_ITEM_|CAJERO_)/.test(rawName)) {
            return null;
        }
        const cleanName = rawName
            .replace(/^(PROD_|FRESCO_|MOSTRADOR_ITEM_|CAJERO_)/, '')
            .replace(/_(Item|Mimbre|Sup|Fondo)(?=_|$)/g, '')
            .replace(/_\d+$/g, '')
            .replace(/_/g, ' ')
            .trim();
        return {
            rawName,
            title: cleanName || 'Producto de bodega',
            zone: rawName.startsWith('FRESCO_') ? 'Frescos' : rawName.startsWith('MOSTRADOR_') ? 'Mostrador' : 'Estantes',
            catalogMatch: null,
            infoNote: null,
        };
    }

    let bestProduct = null;
    if (dictEntry.queries.length && Array.isArray(catalog) && catalog.length) {
        let bestScore = 0;
        catalog.forEach((product) => {
            const haystack = normalizeText(`${product.name} ${product.category}`);
            let score = 0;
            dictEntry.queries.forEach((q, idx) => {
                if (haystack.includes(q)) {
                    score += idx === 0 ? 3 : 1;
                }
            });
            if (score > bestScore) {
                bestScore = score;
                bestProduct = product;
            }
        });
        if (bestScore < 2) {
            bestProduct = null;
        }
    }

    return {
        rawName,
        title: bestProduct ? bestProduct.name : dictEntry.label,
        zone: dictEntry.zone,
        catalogMatch: bestProduct,
        infoNote: dictEntry.infoNote || null,
    };
}

function findNamedAncestor(object) {
    let cursor = object;
    while (cursor) {
        if (cursor.name) {
            if (IGNORED_NODE_PREFIXES.some((prefix) => cursor.name.startsWith(prefix))) {
                return null;
            }
            if (/^(PROD_|FRESCO_|MOSTRADOR_ITEM_|CAJERO_)/.test(cursor.name)) {
                return cursor.name;
            }
        }
        cursor = cursor.parent;
    }
    return null;
}

/**
 * Replace heavy MeshPhysicalMaterial / MeshStandardMaterial on non-reflective matte surfaces
 * with lightweight MeshPhongMaterial, enforce FrontSide on closed solids, and strip
 * expensive multi-pass transmission refraction.
 */
function buildFastMaterial(sourceMat, envTexture) {
    if (!sourceMat) return sourceMat;
    if (sourceMat.userData?.bodegaFastMat) return sourceMat.userData.bodegaFastMat;

    const name = sourceMat.name || '';
    const isKeepTwoSided =
        name.includes('HojaVerde') ||
        name.includes('Vidrio') ||
        name.includes('LinoCrema') ||
        name.includes('Pizarra');
    const side = isKeepTwoSided ? THREE.DoubleSide : THREE.FrontSide;

    const isGlass = name.includes('Vidrio');
    const isLiquid = name.includes('AceiteVeg');
    const isShinyMetal =
        name.includes('Laton') ||
        name.includes('Aluminio') ||
        name.includes('Metal') ||
        name.includes('TapaDorada') ||
        name.includes('Gafete');
    const isEmissive = name.includes('Foco_Emisivo');

    let fastMat;

    if (isGlass || isLiquid || isShinyMetal || isEmissive || name.includes('Marmol')) {
        fastMat = new THREE.MeshStandardMaterial({
            name,
            color: sourceMat.color ? sourceMat.color.clone() : new THREE.Color(0xffffff),
            map: sourceMat.map || null,
            roughness: isGlass ? 0.08 : isLiquid ? 0.16 : (sourceMat.roughness ?? 0.3),
            metalness: isGlass || isLiquid ? 0.04 : (sourceMat.metalness ?? 0.0),
            transparent: isGlass || isLiquid || Boolean(sourceMat.transparent),
            opacity: isGlass ? 0.24 : isLiquid ? 0.85 : (sourceMat.opacity ?? 1),
            depthWrite: isGlass ? false : true,
            side,
            emissive: sourceMat.emissive ? sourceMat.emissive.clone() : new THREE.Color(0x000000),
            emissiveIntensity: sourceMat.emissiveIntensity ?? 1,
        });

        if (envTexture && (isGlass || isShinyMetal)) {
            fastMat.envMap = envTexture;
            fastMat.envMapIntensity = isGlass ? 0.72 : 0.6;
        }
    } else {
        // Ultra-fast Blinn-Phong for walls, floors, wood shelves, cartons, produce & NPC
        const roughness = typeof sourceMat.roughness === 'number' ? sourceMat.roughness : 0.6;
        const shininess = Math.max(4, Math.round((1 - roughness) * 38));
        const specIntensity = roughness >= 0.65 ? 0.05 : 0.14;

        fastMat = new THREE.MeshPhongMaterial({
            name,
            color: sourceMat.color ? sourceMat.color.clone() : new THREE.Color(0xffffff),
            map: sourceMat.map || null,
            specular: new THREE.Color().setScalar(specIntensity),
            shininess,
            transparent: Boolean(sourceMat.transparent),
            opacity: sourceMat.opacity ?? 1,
            side,
            emissive: sourceMat.emissive ? sourceMat.emissive.clone() : new THREE.Color(0x000000),
            emissiveIntensity: sourceMat.emissiveIntensity ?? 1,
        });
    }

    sourceMat.userData.bodegaFastMat = fastMat;
    return fastMat;
}

/**
 * Optimize loaded GLB by converting shaders cleanly, merging static geometry per material
 * and instancing repeated interactive meshes.
 */
function optimizeSceneGraph(gltfScene, catalog, envTexture) {
    gltfScene.updateMatrixWorld(true);

    const staticBuckets = new Map();
    const interactiveBuckets = new Map();
    const interactiveTargets = [];
    const cameraAnchors = [];

    gltfScene.traverse((node) => {
        if (node.name && node.name.startsWith('CAM_')) {
            cameraAnchors.push(node);
            return;
        }

        if (!node.isMesh || node.isSkinnedMesh || Array.isArray(node.material)) return;

        const fastMaterial = buildFastMaterial(node.material, envTexture);
        const rawName = findNamedAncestor(node);
        const meta = rawName ? resolveInteractiveMeta(rawName, catalog) : null;
        const worldMatrix = node.matrixWorld.clone();

        if (meta) {
            const key = `${node.geometry.uuid}:${fastMaterial.uuid}`;
            if (!interactiveBuckets.has(key)) {
                interactiveBuckets.set(key, {
                    geometry: node.geometry,
                    material: fastMaterial,
                    entries: [],
                });
            }
            interactiveBuckets.get(key).entries.push({ node, meta, worldMatrix });
        } else {
            const attrKeys = Object.keys(node.geometry.attributes).sort().join(',');
            const indexed = node.geometry.index ? 'i' : 'n';
            const key = `${fastMaterial.uuid}:${attrKeys}:${indexed}`;
            if (!staticBuckets.has(key)) {
                staticBuckets.set(key, {
                    material: fastMaterial,
                    entries: [],
                });
            }
            staticBuckets.get(key).entries.push({ node, worldMatrix });
        }
    });

    const optimizedRoot = new THREE.Group();

    // Preserve camera anchor nodes safely in scene coordinates
    cameraAnchors.forEach((camNode) => {
        const pos = new THREE.Vector3();
        camNode.getWorldPosition(pos);
        const anchor = new THREE.Object3D();
        anchor.name = camNode.name;
        anchor.position.copy(pos);
        optimizedRoot.add(anchor);
    });

    // 1. Merge static architecture/shelves/decorations per shared material & attributes
    staticBuckets.forEach(({ material, entries }) => {
        if (entries.length === 1) {
            const clone = new THREE.Mesh(entries[0].node.geometry, material);
            clone.applyMatrix4(entries[0].worldMatrix);
            clone.frustumCulled = true;
            optimizedRoot.add(clone);
            return;
        }

        const bakedGeos = [];
        let canMerge = true;
        for (const item of entries) {
            try {
                const cloned = item.node.geometry.clone();
                cloned.applyMatrix4(item.worldMatrix);
                bakedGeos.push(cloned);
            } catch {
                canMerge = false;
                break;
            }
        }

        if (canMerge && bakedGeos.length) {
            const merged = mergeGeometries(bakedGeos, false);
            bakedGeos.forEach((g) => g.dispose());
            if (merged) {
                const mergedMesh = new THREE.Mesh(merged, material);
                mergedMesh.frustumCulled = true;
                optimizedRoot.add(mergedMesh);
                return;
            }
        }

        entries.forEach((item) => {
            const m = new THREE.Mesh(item.node.geometry, material);
            m.applyMatrix4(item.worldMatrix);
            optimizedRoot.add(m);
        });
    });

    // 2. Instance repeated interactive items (products, fruit baskets, etc.)
    interactiveBuckets.forEach(({ geometry, material, entries }) => {
        if (entries.length === 1) {
            const item = entries[0];
            const mesh = new THREE.Mesh(geometry, material);
            mesh.applyMatrix4(item.worldMatrix);
            mesh.userData.bodegaMeta = item.meta;
            optimizedRoot.add(mesh);
            interactiveTargets.push(mesh);
            return;
        }

        const instanced = new THREE.InstancedMesh(geometry, material, entries.length);
        const metas = new Array(entries.length);
        entries.forEach((item, idx) => {
            instanced.setMatrixAt(idx, item.worldMatrix);
            metas[idx] = item.meta;
        });
        instanced.instanceMatrix.needsUpdate = true;
        instanced.computeBoundingSphere();
        instanced.userData.instanceMetas = metas;
        optimizedRoot.add(instanced);
        interactiveTargets.push(instanced);
    });

    optimizedRoot.updateMatrixWorld(true);
    return { root: optimizedRoot, interactiveTargets };
}

export function initBodega3DViewer(stageEl, options = {}) {
    const viewport = stageEl.querySelector('[data-bodega-viewport]');
    const canvas = stageEl.querySelector('[data-bodega-canvas]');
    const posterImg = stageEl.querySelector('[data-bodega-img]');
    const loadingBadge = stageEl.querySelector('[data-bodega-loading]');
    const hoverTip = stageEl.querySelector('[data-bodega-hover-tip]');
    const inspector = stageEl.querySelector('[data-bodega-inspector]');
    const focusBtns = Array.from(stageEl.querySelectorAll('[data-bodega-focus]'));
    const hintLabel = stageEl.querySelector('[data-bodega-hint]');
    const expandBtn = stageEl.querySelector('[data-bodega-expand]');
    const modelUrl = viewport?.dataset.modelUrl;

    if (!viewport || !canvas || !modelUrl) {
        return false;
    }

    let catalog = [];
    try {
        catalog = JSON.parse(viewport.dataset.bodegaCatalog || '[]');
    } catch {
        catalog = [];
    }

    const reducedMotion = Boolean(options.prefersReducedMotion);

    let renderer;
    try {
        renderer = new THREE.WebGLRenderer({
            canvas,
            antialias: true,
            alpha: false,
            powerPreference: 'high-performance',
        });
    } catch {
        return false;
    }

    let adaptiveScaleCap = Math.min(window.devicePixelRatio || 1, 1.25);
    let expandedDprCap = 1.0;
    let slowFrameStreak = 0;
    let lastTickTimestamp = 0;
    let lastWidth = 0;
    let lastHeight = 0;
    let lastDpr = 0;

    renderer.setPixelRatio(adaptiveScaleCap);
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.04;

    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x192315);

    const pmremGenerator = new THREE.PMREMGenerator(renderer);
    const envTarget = pmremGenerator.fromScene(new RoomEnvironment(), 0.04);

    const camera = new THREE.PerspectiveCamera(44, Math.max(viewport.clientWidth, 1) / Math.max(viewport.clientHeight, 1), 0.08, 45);
    camera.position.set(0.28, 1.56, 0.75);

    const controls = new OrbitControls(camera, canvas);
    controls.enableDamping = !reducedMotion;
    controls.dampingFactor = 0.095;
    controls.rotateSpeed = 0.68;
    controls.zoomSpeed = 0.85;
    controls.panSpeed = 0.55;
    controls.minDistance = 0.45;
    controls.maxDistance = 7.5;
    controls.minPolarAngle = Math.PI * 0.16;
    controls.maxPolarAngle = Math.PI * 0.56;
    controls.target.set(0.05, 1.38, -4.6);
    controls.update();

    // 3 luces rápidas optimizadas
    const hemiLight = new THREE.HemisphereLight(0xfff6e5, 0x3b352a, 1.15);
    scene.add(hemiLight);

    const windowSun = new THREE.DirectionalLight(0xffe7c2, 1.7);
    windowSun.position.set(5.2, 4.1, -1.8);
    scene.add(windowSun);

    const frontFill = new THREE.DirectionalLight(0xf4efe4, 0.65);
    frontFill.position.set(-3.2, 2.4, 2.2);
    scene.add(frontFill);

    let sceneRoot = null;
    let interactiveMeshes = [];
    let activeFlight = null;
    let hoveredKey = null;
    let isStageVisible = true;
    let rafId = null;
    let needsRender = true;
    let isOrbiting = false;
    let lastHoverCheck = 0;

    const raycaster = new THREE.Raycaster();
    const pointerNdc = new THREE.Vector2();
    const tmpPos = new THREE.Vector3();
    const tmpTarget = new THREE.Vector3();

    const setActivePresetButton = (key) => {
        focusBtns.forEach((btn) => {
            const active = btn.dataset.bodegaFocus === key;
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    };

    const requestRender = () => {
        needsRender = true;
        if (!rafId && isStageVisible) {
            rafId = requestAnimationFrame(tick);
        }
    };

    const computeTargetDpr = (w) => {
        const expanded = stageEl.classList.contains('bodega-stage-expanded');
        if (expanded) {
            const widthFactor = w > 1600 ? 1600 / w : 1;
            return Math.min(expandedDprCap, Math.max(0.7, widthFactor));
        }
        return adaptiveScaleCap;
    };

    const resizeRenderer = () => {
        const w = Math.max(viewport.clientWidth, 1);
        const h = Math.max(viewport.clientHeight, 1);
        const dpr = computeTargetDpr(w);
        if (w === lastWidth && h === lastHeight && Math.abs(dpr - lastDpr) < 0.02) {
            return;
        }
        lastWidth = w;
        lastHeight = h;
        lastDpr = dpr;

        camera.aspect = w / h;
        camera.updateProjectionMatrix();
        renderer.setPixelRatio(dpr);
        renderer.setSize(w, h, false);
        requestRender();
    };

    const irAVista = (clave) => {
        const preset = VISTAS[clave] || VISTAS.general;
        setActivePresetButton(clave);
        if (hintLabel) hintLabel.textContent = preset.hint;

        if (!sceneRoot) return;

        const nodoPos = sceneRoot.getObjectByName(preset.pos);
        const nodoTarget = sceneRoot.getObjectByName(preset.target);
        if (!nodoPos || !nodoTarget) return;

        nodoPos.getWorldPosition(tmpPos);
        nodoTarget.getWorldPosition(tmpTarget);

        if (reducedMotion) {
            camera.position.copy(tmpPos);
            controls.target.copy(tmpTarget);
            const prevDamping = controls.enableDamping;
            controls.enableDamping = false;
            controls.update();
            controls.enableDamping = prevDamping;
            activeFlight = null;
            requestRender();
            return;
        }

        activeFlight = {
            startTime: performance.now(),
            duration: 900,
            fromPos: camera.position.clone(),
            toPos: tmpPos.clone(),
            fromTarget: controls.target.clone(),
            toTarget: tmpTarget.clone(),
        };
        requestRender();
    };

    const closeInspector = () => {
        if (!inspector) return;
        inspector.classList.add('hidden');
        inspector.replaceChildren();
    };

    const openInspector = (meta) => {
        if (!inspector || !meta) return;

        inspector.replaceChildren();
        inspector.classList.remove('hidden');

        const card = document.createElement('div');
        card.className = 'pointer-events-auto border border-[#192315]/25 bg-[#FAF7EF] p-3.5 text-[#192315] shadow-xl';

        const header = document.createElement('div');
        header.className = 'flex items-start justify-between gap-3';

        const left = document.createElement('div');
        left.className = 'min-w-0';

        const eyebrow = document.createElement('p');
        eyebrow.className = 'text-[10px] font-bold uppercase tracking-[0.16em] text-[#A44A28]';
        eyebrow.textContent = meta.catalogMatch
            ? `${meta.zone} · ${meta.catalogMatch.category}`
            : `Zona ${meta.zone}`;
        left.appendChild(eyebrow);

        const title = document.createElement('h3');
        title.className = 'font-editorial mt-0.5 text-lg font-medium leading-tight text-[#192315]';
        title.textContent = meta.title;
        left.appendChild(title);
        header.appendChild(left);

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'shrink-0 px-1.5 py-0.5 text-xs font-bold text-[#595548] hover:text-[#192315]';
        closeBtn.setAttribute('aria-label', 'Cerrar detalle');
        closeBtn.textContent = '✕';
        closeBtn.addEventListener('click', closeInspector);
        header.appendChild(closeBtn);

        card.appendChild(header);

        const body = document.createElement('div');
        body.className = 'mt-2.5 flex flex-wrap items-center justify-between gap-3 border-t border-[#192315]/12 pt-2.5';

        if (meta.catalogMatch) {
            const priceBox = document.createElement('div');
            const priceEl = document.createElement('p');
            priceEl.className = 'font-mono-num text-base font-semibold text-[#192315]';
            priceEl.textContent = `${meta.catalogMatch.price_formatted} / ${meta.catalogMatch.unit}`;
            priceBox.appendChild(priceEl);

            const stockEl = document.createElement('p');
            stockEl.className = `text-[11px] font-semibold ${meta.catalogMatch.low_stock ? 'text-[#A44A28]' : 'text-[#263822]'}`;
            stockEl.textContent = meta.catalogMatch.status_label;
            priceBox.appendChild(stockEl);
            body.appendChild(priceBox);
        } else {
            const desc = document.createElement('p');
            desc.className = 'max-w-[230px] text-[11px] leading-4 text-[#595548]';
            desc.textContent = meta.infoNote || 'Artículo de vitrina. Consúltanos disponibilidad y precio del día.';
            body.appendChild(desc);
        }

        if (!meta.infoNote) {
            const askBtn = document.createElement('button');
            askBtn.type = 'button';
            askBtn.className = 'rounded-[2px] bg-[#192315] px-3 py-1.5 text-[11px] font-semibold text-[#F4EFE4] transition-colors hover:bg-[#A44A28]';
            askBtn.textContent = meta.catalogMatch ? 'Pedir ahora →' : 'Consultar →';
            askBtn.addEventListener('click', () => {
                const contactSection = document.getElementById('contacto');
                const messageField = contactSection?.querySelector('textarea[name="message"]');
                if (messageField) {
                    messageField.value = `Hola, vi «${meta.title}» en el recorrido 3D de la bodega y quisiera consultar disponibilidad y precio.`;
                    messageField.focus({ preventScroll: true });
                }
                closeInspector();
                if (stageEl.classList.contains('bodega-stage-expanded')) {
                    stageEl.classList.remove('bodega-stage-expanded');
                    document.body.classList.remove('is-3d-expanded');
                    expandBtn?.setAttribute('aria-pressed', 'false');
                    requestAnimationFrame(resizeRenderer);
                }
                if (contactSection) {
                    const top = contactSection.getBoundingClientRect().top + window.scrollY - 76;
                    window.scrollTo({ top: Math.max(0, top), behavior: reducedMotion ? 'auto' : 'smooth' });
                }
            });
            body.appendChild(askBtn);
        }

        card.appendChild(body);
        inspector.appendChild(card);
    };

    const pickObjectAt = (clientX, clientY) => {
        if (!interactiveMeshes.length) return null;
        const rect = canvas.getBoundingClientRect();
        if (!rect.width || !rect.height) return null;

        pointerNdc.x = ((clientX - rect.left) / rect.width) * 2 - 1;
        pointerNdc.y = -((clientY - rect.top) / rect.height) * 2 + 1;

        raycaster.setFromCamera(pointerNdc, camera);
        const hits = raycaster.intersectObjects(interactiveMeshes, false);
        if (!hits.length) return null;

        for (const hit of hits) {
            if (hit.object.isInstancedMesh && typeof hit.instanceId === 'number') {
                const meta = hit.object.userData.instanceMetas?.[hit.instanceId];
                if (meta) return meta;
            } else if (hit.object.userData.bodegaMeta) {
                return hit.object.userData.bodegaMeta;
            }
        }
        return null;
    };

    controls.addEventListener('start', () => {
        activeFlight = null;
        isOrbiting = true;
        requestRender();
    });

    controls.addEventListener('end', () => {
        isOrbiting = false;
        requestRender();
    });

    controls.addEventListener('change', () => {
        requestRender();
    });

    let downX = 0;
    let downY = 0;
    let isPointerDown = false;

    canvas.addEventListener('pointerdown', (e) => {
        isPointerDown = true;
        downX = e.clientX;
        downY = e.clientY;
        canvas.classList.add('is-dragging');
        hoverTip?.classList.add('hidden');
    });

    canvas.addEventListener('pointermove', (e) => {
        if (e.pointerType === 'touch' || isPointerDown || isOrbiting || activeFlight || !interactiveMeshes.length) {
            return;
        }
        const now = performance.now();
        if (now - lastHoverCheck < 55) return;
        lastHoverCheck = now;

        const meta = pickObjectAt(e.clientX, e.clientY);
        const nextKey = meta ? `${meta.rawName}:${meta.title}` : null;

        if (nextKey !== hoveredKey) {
            hoveredKey = nextKey;
            canvas.style.cursor = nextKey ? 'pointer' : 'grab';
            if (hoverTip) {
                if (meta) {
                    hoverTip.textContent = meta.title;
                    hoverTip.classList.remove('hidden');
                } else {
                    hoverTip.classList.add('hidden');
                }
            }
        }
    }, { passive: true });

    canvas.addEventListener('pointerleave', () => {
        hoveredKey = null;
        canvas.style.cursor = 'grab';
        canvas.classList.remove('is-dragging');
        hoverTip?.classList.add('hidden');
    });

    canvas.addEventListener('pointerup', (e) => {
        canvas.classList.remove('is-dragging');
        const wasClick = isPointerDown && Math.hypot(e.clientX - downX, e.clientY - downY) < 6;
        isPointerDown = false;
        if (!wasClick) return;

        const meta = pickObjectAt(e.clientX, e.clientY);
        if (meta) {
            openInspector(meta);
        }
    });

    canvas.addEventListener('dblclick', () => irAVista('general'));

    focusBtns.forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            closeInspector();
            irAVista(btn.dataset.bodegaFocus || 'general');
        });
    });

    expandBtn?.addEventListener('click', () => {
        const isExpanded = stageEl.classList.toggle('bodega-stage-expanded');
        document.body.classList.toggle('is-3d-expanded', isExpanded);
        expandBtn.setAttribute('aria-pressed', isExpanded ? 'true' : 'false');
        expandBtn.title = isExpanded ? 'Salir de vista ampliada' : 'Ampliar visor 3D';
        requestAnimationFrame(resizeRenderer);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        if (inspector && !inspector.classList.contains('hidden')) {
            closeInspector();
        } else if (stageEl.classList.contains('bodega-stage-expanded')) {
            stageEl.classList.remove('bodega-stage-expanded');
            document.body.classList.remove('is-3d-expanded');
            expandBtn?.setAttribute('aria-pressed', 'false');
            requestAnimationFrame(resizeRenderer);
        }
    });

    const tick = (now) => {
        rafId = null;
        if (!isStageVisible) return;

        let keepTicking = false;

        if (activeFlight) {
            const elapsed = now - activeFlight.startTime;
            const rawProgress = Math.min(1, elapsed / activeFlight.duration);
            const eased = easeInOutCubic(rawProgress);

            camera.position.lerpVectors(activeFlight.fromPos, activeFlight.toPos, eased);
            controls.target.lerpVectors(activeFlight.fromTarget, activeFlight.toTarget, eased);

            // Suspender damping mientras se ejecuta el vuelo suavizado para cero tirones
            const prevDamping = controls.enableDamping;
            controls.enableDamping = false;
            controls.update();
            controls.enableDamping = prevDamping;

            needsRender = true;
            if (rawProgress >= 1) {
                activeFlight = null;
            } else {
                keepTicking = true;
            }
        } else if (controls.update()) {
            needsRender = true;
            keepTicking = true;
        }

        if (needsRender) {
            renderer.render(scene, camera);
            needsRender = false;

            if (lastTickTimestamp > 0 && (keepTicking || activeFlight)) {
                const vsyncDelta = now - lastTickTimestamp;
                if (vsyncDelta > 34) {
                    slowFrameStreak += 1;
                    if (slowFrameStreak >= 4) {
                        slowFrameStreak = 0;
                        if (stageEl.classList.contains('bodega-stage-expanded') && expandedDprCap > 0.78) {
                            expandedDprCap = 0.78;
                            resizeRenderer();
                        } else if (adaptiveScaleCap > 0.85) {
                            adaptiveScaleCap = 0.85;
                            resizeRenderer();
                        }
                    }
                } else {
                    slowFrameStreak = 0;
                }
            }
            lastTickTimestamp = (keepTicking || activeFlight) ? now : 0;
        }

        if (keepTicking || activeFlight) {
            rafId = requestAnimationFrame(tick);
        }
    };

    const resizeObserver = typeof ResizeObserver !== 'undefined'
        ? new ResizeObserver(() => resizeRenderer())
        : null;
    resizeObserver?.observe(viewport);

    const visibilityObserver = typeof IntersectionObserver !== 'undefined'
        ? new IntersectionObserver((entries) => {
            isStageVisible = entries.some((entry) => entry.isIntersecting);
            if (isStageVisible) {
                requestRender();
            }
        }, { threshold: 0.05 })
        : null;
    visibilityObserver?.observe(viewport);

    const loader = new GLTFLoader();
    loader.load(
        modelUrl,
        (gltf) => {
            const optimized = optimizeSceneGraph(gltf.scene, catalog, envTarget.texture);
            sceneRoot = optimized.root;
            interactiveMeshes = optimized.interactiveTargets;
            scene.add(sceneRoot);

            const initialPos = sceneRoot.getObjectByName(VISTAS.general.pos);
            const initialTarget = sceneRoot.getObjectByName(VISTAS.general.target);
            if (initialPos && initialTarget) {
                initialPos.getWorldPosition(tmpPos);
                initialTarget.getWorldPosition(tmpTarget);
                camera.position.copy(tmpPos);
                controls.target.copy(tmpTarget);
                const prevDamping = controls.enableDamping;
                controls.enableDamping = false;
                controls.update();
                controls.enableDamping = prevDamping;
            }

            stageEl.dataset.mode3d = 'ready';
            resizeRenderer();
            renderer.render(scene, camera);

            canvas.classList.add('is-ready');
            posterImg?.classList.add('is-hidden-3d');
            loadingBadge?.classList.add('hidden');
            if (hintLabel) hintLabel.textContent = VISTAS.general.hint;
        },
        undefined,
        () => {
            loadingBadge?.classList.add('hidden');
            envTarget.dispose();
            pmremGenerator.dispose();
            renderer.dispose();
        }
    );

    return true;
}
