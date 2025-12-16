[English Version](README_en.md)

# 餐厅管理系统

## 简介

基于 PHP + MySQL 的轻量餐厅管理系统，覆盖用户端下单与积分、餐桌预订、积分兑换与历史记录；以及员工端订单处理、预订管理、原料库存补货与个人资料（头像）维护。项目无需框架，原生 HTML/CSS/JS，仅依赖 `mysqli` 连接 MySQL，适合课程作业与演示场景。

## 功能概览

- 用户端
  - 注册 / 登录 / 登出
  - 商品下单：购物车、备注，消费累计积分（1 元=1 积分）
  - 积分与会员等级显示：Bronze / Silver / Gold
  - 餐桌预订：按日期、时段与桌位容量校验占用
  - 积分兑换礼品：扣减积分并记录兑换
  - 历史记录：订单与预订列表
- 员工端
  - 订单管理：完成 / 取消；完成时扣减相关原料库存
  - 预订管理：取消（删除记录）
  - 原料管理：补货并动态更新“充足/需补货”状态
  - 个人资料：头像上传/删除（最大 2MB，JPEG/PNG/GIF）
- 数据初始化
  - 首次运行自动插入会员等级、商品、原料、桌位、礼品以及一个员工账号（`staff/123456`）

## 技术栈

- 后端：PHP 7+，`mysqli`
- 前端：HTML、CSS、原生 JS
- 数据库：MySQL（`restaurant.sql` 建库建表，字符集 `utf8mb4`）

## 快速开始

- 将项目文件夹 `pro` 放入 `XAMPP/htdocs`
- 启动 Apache 与 MySQL
- 在 MySQL 中执行 `restaurant.sql`
  - 已包含：`CREATE DATABASE restaurant` 与全部表结构
- 配置数据库连接 `config.php`
  - 默认：
    - `DB_HOST=localhost`
    - `DB_USER=root`
    - `DB_PASS=`（空）
    - `DB_NAME=restaurant`
- 浏览器访问 `http://localhost/pro/login.php`

## 演示账号

- 用户：`user / 123456`（如不存在，请先注册）
- 员工：`staff / 123456`（`config.php` 首次运行自动插入）

## 目录结构

- `common.css`：通用样式
- `config.php`：数据库连接、初始化数据、认证/注册/登出、会员等级
- `if.php`：业务函数（订单/预订/原料/兑换等）
- `login.php` / `register.php` / `logout.php`
- 用户端页面：
  - `user_order.php`：下单与购物车
  - `user_reservation.php`：餐桌预订
  - `user_redeem.php`：积分兑换
  - `user_history.php`：历史记录
- 员工端页面：
  - `staff_orders.php`：订单管理
  - `staff_reservations.php`：预订管理
  - `staff_materials.php`：原料库存
  - `staff_profile.php`：头像与资料
- `restaurant.sql`：数据库结构与默认库名 `restaurant`
- `dump/bigdump.php`：大文件导入辅助（可选）
- `user.css` / `staff.css`：界面样式

## 关键实现与说明

- 会员等级与积分：`config.php:251` 起按积分计算等级；消费积分累积在 `if.php:291`
- 订单聚合与状态：`if.php:66` 汇总订单条目；`if.php:451` 更新状态并在完成时扣减原料
- 预订占用校验：`if.php:210` 检查指定桌位在某时段是否已占用
- 积分兑换与记录：`if.php:409` 扣减积分并写入 `Exchange`
- 头像存储：`restaurant.sql:169` 将 `Avatar` 存储于 `LONGBLOB`，页面在 `staff_profile.php`

## 注意事项

- 密码以数字形式比较，未进行哈希与加盐；仅用于演示
- `cancel_reservation.php` 依赖 `getReservationDetails` 尚未在 `if.php` 实现，如需启用请补充
- 本项目不包含自动化测试与部署脚本
