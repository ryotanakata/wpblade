<?php

namespace WpBlade\Services;

use WP_Post;

/**
 * コラム（CPT: column）の表示用データ組み立て。
 *
 * テンプレート（薄い Controller）に if / foreach を伴う加工を書かないため、
 * サムネイル解決やカード配列の組み立てをここに集約する。
 *
 * @package WpBlade\Services
 */
class ColumnService
{
    /**
     * アーカイブ一覧用のカードデータを組み立てる。
     *
     * @param  array<int, WP_Post> $posts
     * @return list<array{
     *     id: int,
     *     permalink: string,
     *     date: string,
     *     date_attr: string,
     *     title: string,
     *     excerpt: string,
     *     thumbnail: array{url: string, width: int, height: int, alt: string}|null
     * }>
     */
    public function buildArchiveItems(array $posts): array
    {
        return array_map(
            fn(WP_Post $post): array => [
                "id" => $post->ID,
                "permalink" => esc_url((string) get_permalink($post)),
                "date" => (string) get_the_date("Y.m.d", $post),
                "date_attr" => (string) get_the_date("Y-m-d", $post),
                "title" => get_the_title($post),
                "excerpt" => get_the_excerpt($post),
                "thumbnail" => $this->buildThumbnail($post->ID, "medium"),
            ],
            $posts,
        );
    }

    /**
     * ページネーションリンク（ページ番号 → URL）の辞書を組み立てる。
     *
     * @param  list<int> $page_numbers
     * @return array<int, string>
     */
    public function buildPaginationLinks(array $page_numbers): array
    {
        return array_combine(
            $page_numbers,
            array_map(fn($n) => esc_url(get_pagenum_link($n, false)), $page_numbers),
        );
    }

    /**
     * サムネイル情報（URL・実寸・alt）を組み立てる。未設定なら null。
     *
     * @param  int    $post_id
     * @param  string $size    WordPress の画像サイズ名
     * @return array{url: string, width: int, height: int, alt: string}|null
     */
    public function buildThumbnail(int $post_id, string $size = "large"): ?array
    {
        $thumb_id = get_post_thumbnail_id($post_id);
        if (!$thumb_id) {
            return null;
        }

        $src = wp_get_attachment_image_src($thumb_id, $size);
        if (!$src) {
            return null;
        }

        $alt = (string) get_post_meta(
            $thumb_id,
            "_wp_attachment_image_alt",
            true,
        );

        return [
            "url" => esc_url((string) $src[0]),
            "width" => (int) $src[1],
            "height" => (int) $src[2],
            // 代替テキスト未入力のメディアでも空 alt にならないよう記事タイトルで補う
            "alt" => $alt !== "" ? $alt : (string) get_the_title($post_id),
        ];
    }
}
