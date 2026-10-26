<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/auth/auth.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/services/pickup_orders.php';
require_once __DIR__ . '/../../app/services/user_cart.php';

$user = current_user();
if ($user === null) {
    header('Location: ../login.php');
    exit;
}
if (($user['role'] ?? '') !== 'user') {
    http_response_code(403);
    exit('Access denied.');
}
$userId = filter_var($user['id'] ?? null, FILTER_VALIDATE_INT);
if ($userId === false || $userId < 1) {
    http_response_code(403);
    exit('Access denied.');
}

$cartError = '';
$cartWantsJson = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
try {
    ensure_user_cart_loaded(database_connection(), (int) $userId);
} catch (Throwable $error) {
    error_log('Saved user cart could not be loaded for editing.');
    $cartError = 'Your saved bag is temporarily unavailable. Please try again later.';
    $_SESSION['cart'] = isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? $_SESSION['cart'] : [];
}
$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cartError === '') {
    if (!csrf_token_is_valid($_POST['_csrf'] ?? null)) {
        $cartError = 'Your form expired. Please try again.';
    } else {
        try {
            $action = $_POST['_action'] ?? '';
            if ($action === 'add') {
                $id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
                $quantity = filter_var($_POST['quantity'] ?? 1, FILTER_VALIDATE_INT);
                if ($id === false || $id < 1 || $quantity === false || $quantity < 1 || $quantity > 99) {
                    throw new InvalidArgumentException('Choose a valid product and quantity.');
                }
                $product = database_connection()->prepare(
                    'SELECT price, GREATEST(stock_on_hand - reserved_quantity, 0) AS available_quantity
                     FROM products WHERE id = :id AND is_active = 1 LIMIT 1'
                );
                $product->execute(['id' => $id]);
                $productRow = $product->fetch();
                if (!$productRow) {
                    throw new InvalidArgumentException('This product is no longer available.');
                }
                $available = (int) $productRow['available_quantity'];
                $current = (int) ($_SESSION['cart'][$id] ?? 0);
                if (count($_SESSION['cart']) >= 50 && $current === 0) {
                    throw new InvalidArgumentException('Your cart can contain up to 50 different products.');
                }
                if ($current + $quantity > 99) {
                    throw new InvalidArgumentException('A cart item cannot exceed a quantity of 99.');
                }
                $offer = find_active_product_offer(database_connection(), (int) $id);
                $priceBreakdown = calculate_product_offer((string) $productRow['price'], $current + $quantity, $offer);
                if ($priceBreakdown['stock_quantity'] > $available) {
                    throw new InvalidArgumentException('There is not enough currently available stock for this quantity.');
                }
                save_user_cart_item(database_connection(), (int) $userId, (int) $id, $current + $quantity);
                $_SESSION['cart'][$id] = $current + $quantity;
                $_SESSION['success_message'] = 'Added to your cart.';
                header('Location: index.php');
                exit;
            }
            if ($action === 'update') {
                $id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
                $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
                if ($id === false || $id < 1 || $quantity === false || $quantity < 0 || $quantity > 99) {
                    throw new InvalidArgumentException('Choose a valid cart quantity from zero to 99.');
                }
                if ($quantity === 0) {
                    remove_user_cart_item(database_connection(), (int) $userId, (int) $id);
                    unset($_SESSION['cart'][$id]);
                } else {
                    save_user_cart_item(database_connection(), (int) $userId, (int) $id, (int) $quantity);
                    $_SESSION['cart'][$id] = $quantity;
                }
                if ($cartWantsJson) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['ok' => true]);
                    exit;
                }
                header('Location: index.php');
                exit;
            }
            if ($action === 'remove') {
                $id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
                if ($id === false || $id < 1) {
                    throw new InvalidArgumentException('Choose a valid cart item.');
                }
                remove_user_cart_item(database_connection(), (int) $userId, (int) $id);
                unset($_SESSION['cart'][$id]);
                if ($cartWantsJson) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['ok' => true]);
                    exit;
                }
                header('Location: index.php');
                exit;
            }
            if ($action === 'clear') {
                clear_user_cart(database_connection(), (int) $userId);
                $_SESSION['cart'] = [];
                if ($cartWantsJson) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['ok' => true]);
                    exit;
                }
                header('Location: index.php');
                exit;
            }
            if ($action === 'checkout') {
                $method = $_POST['payment_method'] ?? '';
                if (!is_string($method)) {
                    throw new InvalidArgumentException('Choose a payment method.');
                }
                $quantities = parse_pickup_order_quantities($_SESSION['cart']);
                $orderId = create_pickup_order(database_connection(), (int) $userId, $quantities, $method);
                $_SESSION['cart'] = [];
                $_SESSION['success_message'] = 'Your order was placed and is awaiting payment review. Products are reserved for 48 hours.';
                if ($cartWantsJson) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['ok' => true, 'redirect' => '../../user/account/orders/view.php?id=' . $orderId]);
                    exit;
                }
                header('Location: ../../user/account/orders/view.php?id=' . $orderId);
                exit;
            }
            throw new InvalidArgumentException('Choose a valid cart action.');
        } catch (InvalidArgumentException|DomainException $error) {
            $cartError = $error->getMessage();
        } catch (Throwable $error) {
            error_log('Cart action could not be completed.');
            $cartError = 'We could not update your cart right now. Please try again.';
        }
    }
}

if ($cartWantsJson && $cartError !== '') {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => $cartError]);
    exit;
}
$_SESSION['cart_action_error'] = $cartError !== '' ? $cartError : 'Choose a valid cart action.';
header('Location: index.php');
exit;
