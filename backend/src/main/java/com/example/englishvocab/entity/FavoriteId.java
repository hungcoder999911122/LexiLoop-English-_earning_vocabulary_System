package com.example.englishvocab.entity;

import jakarta.persistence.Column;
import jakarta.persistence.Embeddable;
import lombok.EqualsAndHashCode;
import lombok.Getter;
import lombok.Setter;

import java.io.Serializable;

@Getter
@Setter
@EqualsAndHashCode
@Embeddable
public class FavoriteId implements Serializable {
    @Column(name = "userID")
    private Integer userID;

    @Column(name = "vocabularyID")
    private Integer vocabularyID;
}
