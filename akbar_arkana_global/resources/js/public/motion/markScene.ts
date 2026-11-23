import {
    AmbientLight,
    DirectionalLight,
    ExtrudeGeometry,
    Group,
    Mesh,
    MeshStandardMaterial,
    PerspectiveCamera,
    Scene,
    Shape,
    WebGLRenderer,
} from 'three';
import { blueContour, navyContours } from './markContours';

export function mountMarkScene(
    container: HTMLElement,
    setReady: (ready: boolean) => void,
    scrollRotation = false,
) {
    const renderer = new WebGLRenderer({ alpha: true, antialias: true });
    const canvas = renderer.domElement;
    const scene = new Scene();
    const camera = new PerspectiveCamera(33, 1, 0.1, 100);
    const mark = new Group();
    const face = new MeshStandardMaterial({
        color: 0x082955,
        metalness: 0.47,
        roughness: 0.28,
    });
    const edge = new MeshStandardMaterial({
        color: 0x152b4b,
        metalness: 0.48,
        roughness: 0.36,
    });
    const accent = new MeshStandardMaterial({
        color: 0x1666ff,
        emissive: 0x06258a,
        emissiveIntensity: 0.36,
        metalness: 0.27,
        roughness: 0.28,
    });

    for (const [index, points] of [...navyContours, blueContour].entries()) {
        const shape = new Shape();
        points.forEach(([x, y], i) => {
            const method = i === 0 ? 'moveTo' : 'lineTo';
            shape[method]((x - 270) / 62, (93 - y) / 62);
        });
        shape.closePath();
        const geometry = new ExtrudeGeometry(shape, {
            depth: 0.82,
            bevelEnabled: true,
            bevelSize: 0.037,
            bevelThickness: 0.035,
            bevelSegments: 3,
            steps: 1,
        });
        geometry.translate(0, 0, -0.41);
        mark.add(
            new Mesh(
                geometry,
                index === navyContours.length ? [accent, edge] : [face, edge],
            ),
        );
    }

    scene.add(mark, new AmbientLight(0xbacdfb, 1.55));
    const key = new DirectionalLight(0xe5efff, 3.6);
    key.position.set(-4, 6, 7);
    const rim = new DirectionalLight(0x407eff, 5);
    rim.position.set(4, -3, 4);
    scene.add(key, rim);

    canvas.setAttribute('aria-hidden', 'true');
    container.append(canvas);

    const pointer = window.matchMedia('(pointer: fine)');
    let visible = false;
    let lost = false;
    let firstFrame = true;
    let frame = 0;
    let previous = 0;
    let elapsed = 0;
    let targetX = 0;
    let targetY = 0;
    let tiltX = 0;
    let tiltY = 0;

    function render(now: number) {
        frame = 0;
        if (!visible || document.hidden || lost) return;
        const delta = previous ? Math.min(now - previous, 50) / 1000 : 0;
        elapsed += delta;
        previous = now;
        const ease = 1 - Math.exp(-3 * delta);
        tiltX += (targetX - tiltX) * ease;
        tiltY += (targetY - tiltY) * ease;
        mark.position.x = tiltX * 0.065;
        canvas.style.transform = `translateY(${Math.sin(elapsed * 0.72) * 6}px)`;
        key.position.set(-4 + tiltX * 1.35, 5 - tiltY * 1.05, 6);
        mark.rotation.set(
            0.22 + tiltY * 0.032,
            (scrollRotation ? window.scrollY / window.innerHeight * Math.PI : 0) - 0.32 + Math.sin(elapsed * 0.57) * 0.04 + tiltX * 0.032,
            -0.105 + tiltX * 0.012,
        );
        renderer.render(scene, camera);
        if (firstFrame) {
            firstFrame = false;
            setReady(true);
        }
        frame = requestAnimationFrame(render);
    }

    function sync() {
        if (!visible || document.hidden || lost) {
            cancelAnimationFrame(frame);
            frame = 0;
            previous = 0;
        } else if (!frame) {
            frame = requestAnimationFrame(render);
        }
    }

    function move(event: PointerEvent) {
        if (!pointer.matches || !visible) return;
        targetX = (event.clientX / window.innerWidth - 0.5) * 2;
        targetY = (event.clientY / window.innerHeight - 0.5) * 2;
    }

    function clearPointer() {
        targetX = targetY = 0;
    }

    function contextLost(event: Event) {
        event.preventDefault();
        lost = true;
        setReady(false);
        sync();
    }

    function contextRestored() {
        lost = false;
        firstFrame = true;
        sync();
    }

    function resize() {
        const { clientWidth: width, clientHeight: height } = container;
        if (!width || !height) return;
        camera.aspect = width / height;
        camera.position.z = Math.max(6.05, 12.05 / camera.aspect);
        camera.updateProjectionMatrix();
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
        renderer.setSize(width, height, false);
    }

    const bounds = new IntersectionObserver(([entry]) => {
        visible = entry.isIntersecting;
        sync();
    });
    const size = new ResizeObserver(resize);
    bounds.observe(container);
    size.observe(container);
    resize();
    document.addEventListener('visibilitychange', sync);
    window.addEventListener('pointermove', move, { passive: true });
    document.documentElement.addEventListener('pointerleave', clearPointer);
    canvas.addEventListener('webglcontextlost', contextLost);
    canvas.addEventListener('webglcontextrestored', contextRestored);

    return () => {
        cancelAnimationFrame(frame);
        bounds.disconnect();
        size.disconnect();
        document.removeEventListener('visibilitychange', sync);
        window.removeEventListener('pointermove', move);
        document.documentElement.removeEventListener(
            'pointerleave',
            clearPointer,
        );
        canvas.removeEventListener('webglcontextlost', contextLost);
        canvas.removeEventListener('webglcontextrestored', contextRestored);
        mark.traverse((child) => {
            if (child instanceof Mesh) child.geometry.dispose();
        });
        face.dispose();
        edge.dispose();
        accent.dispose();
        renderer.dispose();
        renderer.forceContextLoss();
        canvas.remove();
    };
}
