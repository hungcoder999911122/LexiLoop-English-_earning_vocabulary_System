package com.example.englishvocab.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;

import java.time.LocalDateTime;

@Getter
@Setter
@Entity
@Table(name = "Favorites")
public class Favorite {
    @EmbeddedId
    private FavoriteId id = new FavoriteId();

    @ManyToOne(optional = false)
    @MapsId("userID")
    @JoinColumn(name = "userID")
    private User user;

    @ManyToOne(optional = false)
    @MapsId("vocabularyID")
    @JoinColumn(name = "vocabularyID")
    private Vocabulary vocabulary;

    @Column(name = "created_at", insertable = false, updatable = false)
    private LocalDateTime createdAt;
}
